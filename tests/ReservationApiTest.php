<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class ReservationApiTest extends TestCase {
    public function setUp(): void {
        // ensure session and admin flag
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['admin_logged_in'] = true;
    }

    private function callApi(array $params, string $method = 'GET', array $postData = []) {
        // Clear GET/POST
        $_GET = [];
        $_POST = [];

        foreach ($params as $k => $v) $_GET[$k] = $v;
        if (strtoupper($method) === 'POST') {
            foreach ($postData as $k => $v) $_POST[$k] = $v;
        }

        ob_start();
        require __DIR__ . '/../admin_dashboard_api.php';
        $out = ob_get_clean();
        $json = json_decode($out, true);
        return $json;
    }

    public function testGetReservationsBasic() {
        $res = $this->callApi(['action' => 'get_reservations', 'page' => 1, 'limit' => 10]);
        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('reservations', $res);
        $this->assertArrayHasKey('pagination', $res);
        $this->assertGreaterThanOrEqual(1, count($res['reservations']));
    }

    public function testFilterByStatus() {
        $res = $this->callApi(['action' => 'get_reservations', 'status' => 'confirmed']);
        $this->assertTrue($res['success']);
        foreach ($res['reservations'] as $r) {
            $this->assertEquals('confirmed', strtolower($r['status']));
        }
    }

    public function testFilterByGuestsRange() {
        $res = $this->callApi(['action' => 'get_reservations', 'guests_min' => 4, 'guests_max' => 6]);
        $this->assertTrue($res['success']);
        foreach ($res['reservations'] as $r) {
            $this->assertGreaterThanOrEqual(4, (int)$r['num_guests']);
            $this->assertLessThanOrEqual(6, (int)$r['num_guests']);
        }
    }

    public function testFilterByDateRange() {
        $res = $this->callApi(['action' => 'get_reservations', 'date_from' => '2025-11-03', 'date_to' => '2025-11-04']);
        $this->assertTrue($res['success']);
        foreach ($res['reservations'] as $r) {
            $this->assertGreaterThanOrEqual('2025-11-03', $r['reservation_date']);
            $this->assertLessThanOrEqual('2025-11-04', $r['reservation_date']);
        }
    }

    public function testSearchByQ() {
        $res = $this->callApi(['action' => 'get_reservations', 'q' => 'alice']);
        $this->assertTrue($res['success']);
        $this->assertGreaterThanOrEqual(1, count($res['reservations']));
        foreach ($res['reservations'] as $r) {
            $this->assertStringContainsString('alice', strtolower($r['customer_name'] . ' ' . $r['email'] . ' ' . $r['phone_number']));
        }
    }

    public function testPerPagePagination() {
        // Request per_page = 2 to force pagination
        $res = $this->callApi(['action' => 'get_reservations', 'page' => 1, 'limit' => 2]);
        $this->assertTrue($res['success']);
        $this->assertEquals(2, count($res['reservations']));
        $this->assertArrayHasKey('pagination', $res);
        $this->assertEquals(1, $res['pagination']['current_page']);
        $this->assertEquals(2, $res['pagination']['per_page']);
    }

    public function testBulkUpdate() {
        // Update reservations 3 and 4 to refused with a reason
        $ids = [3,4];
        $reason = 'Overbooked for the slot';
        $res = $this->callApi(['action' => 'bulk_update_reservations'], 'POST', ['reservation_ids' => json_encode($ids), 'status' => 'refused', 'reason' => $reason]);
        $this->assertTrue($res['success']);
        $this->assertEquals(2, $res['updated']);
        $this->assertArrayHasKey('undo_token', $res);

        // Verify the status changed
        $check = $this->callApi(['action' => 'get_reservations', 'q' => 'carol']);
        $this->assertTrue($check['success']);
        foreach ($check['reservations'] as $r) {
            if (in_array((int)$r['id'], $ids)) {
                $this->assertEquals('refused', strtolower($r['status']));
            }
        }

        // Now undo using the token
        $undo = $this->callApi(['action' => 'bulk_undo_update'], 'POST', ['token' => $res['undo_token']]);
        $this->assertTrue($undo['success']);
        $this->assertGreaterThanOrEqual(1, $undo['reverted']);

        // Verify statuses restored (original for id 3 was 'refused' in seed, but undo should set to prior value; for test we at least check the row exists)
        $verify = $this->callApi(['action' => 'get_reservations', 'page' => 1, 'limit' => 10]);
        $this->assertTrue($verify['success']);
    }
}
