<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Initialize cart
if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

// Helper
function slugify($text)
{
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
  $text = preg_replace('~[^-\w]+~', '', $text);
  $text = trim($text, '-');
  $text = preg_replace('~-+~', '-', $text);
  $text = strtolower($text);
  return empty($text) ? 'n-a-' . substr(md5(uniqid(rand(), true)), 0, 8) : $text;
}

function getCartItemCount()
{
  $count = 0;
  foreach ($_SESSION['cart'] as $item) {
    $count += $item['quantity'];
  }
  return $count;
}

$showThankYouModal = false;
$order_summary = $_SESSION['order_details'] ?? '';
$customer_name = $_SESSION['customer_name'] ?? '';

if (isset($_GET['payment']) && $_GET['payment'] === 'success' && $order_summary && $customer_name) {
  $showThankYouModal = true;
  unset($_SESSION['order_details'], $_SESSION['customer_name']);
}
?>

<?php
// Send email using PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
if (isset($_GET['payment']) && $_GET['payment'] === 'success' && isset($_GET['session_id'])) {
  require 'stripe-php/init.php';
  \Stripe\Stripe::setApiKey('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');

  try {
    $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);
    $customer_name = $session->customer_details->name;
    $customer_email = $session->customer_details->email;

    $mail = new PHPMailer(true);

    try {
      $mail->isSMTP();
      $mail->Host = 'smtp.gmail.com';
      $mail->SMTPAuth = true;
      $mail->Username = 'sloughhaveli@gmail.com';
      $mail->Password = 'qxzemmditlemgqph';
      $mail->SMTPSecure = 'tls';
      $mail->Port = 587;

      $mail->setFrom('sloughhaveli@gmail.com', 'Haveli');
      $mail->addAddress($customer_email, $customer_name);

      $mail->isHTML(true);
      $mail->Subject = "Thank you for your order, $customer_name!";
      $mail->Body = $order_summary;
      $mail->AltBody = strip_tags($order_summary);

      $mail->send();
    } catch (Exception $e) {
      error_log("Mail Error: " . $mail->ErrorInfo);
    }
  } catch (Exception $e) {
    error_log("Stripe Error: " . $e->getMessage());
  }
}
?>

<?php
// Fetch recent published blog posts for homepage slider
require_once __DIR__ . '/db_config.php';
try {
  $__pdo_slider = getDBConnection();
  $stmt_slider = $__pdo_slider->prepare('SELECT id, title, slug, excerpt, featured_image FROM posts WHERE status = "published" AND (published_at IS NULL OR published_at <= NOW()) ORDER BY published_at DESC, created_at DESC LIMIT 6');
  $stmt_slider->execute();
  $recent_posts_slider = $stmt_slider->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $recent_posts_slider = [];
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <!-- Enhanced SEO Meta Tags -->
  <title>Haveli Restaurant & Banquet Hall | Authentic Indian Cuisine | Slough, UK</title>
  <meta name="description" content="Experience authentic Indian cuisine at Haveli Restaurant & Banquet Hall in Slough. Specializing in traditional dishes, wedding receptions, and milestone celebrations. Book your table today!" />
  <meta name="keywords" content="Indian restaurant, banquet hall, wedding venue, authentic Indian food, Slough restaurant, Indian cuisine, catering services, special occasions" />
  <meta name="author" content="Haveli Restaurant" />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="https://haveli.co.uk/" />
  
  <!-- Open Graph Tags for Social Media -->
  <meta property="og:title" content="Haveli Restaurant & Banquet Hall | Authentic Indian Cuisine" />
  <meta property="og:description" content="Experience authentic Indian cuisine at Haveli Restaurant & Banquet Hall in Slough. Specializing in traditional dishes, wedding receptions, and milestone celebrations." />
  <meta property="og:image" content="https://haveli.co.uk/assets/logo.png" />
  <meta property="og:url" content="https://haveli.co.uk/" />
  <meta property="og:type" content="restaurant" />
  <meta property="og:site_name" content="Haveli Restaurant" />
  <meta property="og:locale" content="en_GB" />
  
  <!-- Twitter Card Tags -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="Haveli Restaurant & Banquet Hall | Authentic Indian Cuisine" />
  <meta name="twitter:description" content="Experience authentic Indian cuisine at Haveli Restaurant & Banquet Hall in Slough. Specializing in traditional dishes, wedding receptions, and milestone celebrations." />
  <meta name="twitter:image" content="https://haveli.co.uk/assets/logo.png" />
  
  <!-- Favicon -->
  <link rel="icon" type="image/png" href="assets/logo.png" />
  <link rel="apple-touch-icon" href="assets/logo.png" />
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
  
  <!-- Restaurant Schema Markup (JSON-LD) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Restaurant",
    "name": "Haveli Restaurant & Banquet Hall",
    "description": "Authentic Indian restaurant and banquet hall specializing in traditional cuisine, weddings, receptions, and milestone celebrations in Slough, UK.",
    "image": [
      "https://haveli.co.uk/assets/slide1.jpg",
      "https://haveli.co.uk/assets/slide2.jpg",
      "https://haveli.co.uk/assets/slide3.jpg",
      "https://haveli.co.uk/assets/logo.png"
    ],
    "logo": "https://haveli.co.uk/assets/logo.png",
    "url": "https://haveli.co.uk",
    "telephone": "+44-1753-123456",
    "email": "info@haveli.co.uk",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Your Street Address",
      "addressLocality": "Slough",
      "addressRegion": "Berkshire",
      "postalCode": "SL1 1AA",
      "addressCountry": "GB"
    },
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": 51.5074,
      "longitude": -0.7594
    },
    "openingHoursSpecification": [
      {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
        "opens": "17:00",
        "closes": "23:00"
      },
      {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Saturday", "Sunday"],
        "opens": "12:00",
        "closes": "23:00"
      }
    ],
    "servesCuisine": ["Indian", "Asian"],
    "priceRange": "$$",
    "acceptsReservations": true,
    "hasMenu": "https://haveli.co.uk/#menu",
    "aggregateRating": {
      "@type": "AggregateRating",
      "ratingValue": "4.8",
      "reviewCount": "125",
      "bestRating": "5",
      "worstRating": "1"
    },
    "review": [
      {
        "@type": "Review",
        "reviewRating": {
          "@type": "Rating",
          "ratingValue": "5",
          "bestRating": "5"
        },
        "author": {
          "@type": "Person",
          "name": "Sarah Johnson"
        },
        "reviewBody": "Amazing authentic Indian food and excellent service. Perfect venue for special occasions!"
      }
    ],
    "amenityFeature": [
      {
        "@type": "LocationFeatureSpecification",
        "name": "Banquet Hall",
        "value": true
      },
      {
        "@type": "LocationFeatureSpecification", 
        "name": "Wedding Venue",
        "value": true
      },
      {
        "@type": "LocationFeatureSpecification",
        "name": "Catering Services",
        "value": true
      },
      {
        "@type": "LocationFeatureSpecification",
        "name": "Private Events",
        "value": true
      }
    ]
  }
  </script>
  
  <!-- Menu Schema Markup -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Menu",
    "name": "Haveli Restaurant Menu",
    "description": "Authentic Indian cuisine featuring traditional dishes and modern interpretations",
    "hasMenuSection": [
      {
        "@type": "MenuSection",
        "name": "Starters & Appetizers",
        "hasMenuItem": [
          {
            "@type": "MenuItem",
            "name": "Samosa (V)",
            "description": "Crispy pastries filled with spiced potatoes and peas",
            "offers": {
              "@type": "Offer",
              "price": "4.99",
              "priceCurrency": "GBP"
            }
          }
        ]
      },
      {
        "@type": "MenuSection", 
        "name": "Main Courses",
        "hasMenuItem": [
          {
            "@type": "MenuItem",
            "name": "Chicken Biryani (GF)",
            "description": "Aromatic basmati rice with tender chicken and exotic spices",
            "offers": {
              "@type": "Offer",
              "price": "12.99",
              "priceCurrency": "GBP"
            }
          }
        ]
      }
    ]
  }
  </script>
  
  <!-- Local Business Schema -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "Haveli Restaurant & Banquet Hall",
    "description": "Premier Indian restaurant and event venue in Slough",
    "url": "https://haveli.co.uk",
    "telephone": "+44-1753-123456",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Your Street Address",
      "addressLocality": "Slough",
      "addressRegion": "Berkshire", 
      "postalCode": "SL1 1AA",
      "addressCountry": "GB"
    },
    "paymentAccepted": "Cash, Credit Card, Debit Card",
    "currenciesAccepted": "GBP"
  }
  </script>

  <!-- Toast Notification Styles -->
  <style>
    .toast-container {
      position: fixed;
      /* place toast below the sticky header to avoid being hidden
         header height is 70px (see .header in style.css) */
      top: calc(70px + 12px);
      left: 50%;
      transform: translateX(-50%);
      /* ensure toast displays above regular content (header may have very large z-index;
         placing it below the header avoids fighting that). */
      z-index: 10005;
      pointer-events: none;
    }

    .toast-notification {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      padding: 16px 24px;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(40, 167, 69, 0.3);
      font-size: 16px;
      font-weight: 500;
      text-align: center;
      max-width: 320px;
      margin: 0 auto 10px;
      opacity: 0;
      transform: translateY(-100px);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      pointer-events: auto;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .toast-notification.error {
      background: linear-gradient(135deg, #dc3545, #e74c3c);
      box-shadow: 0 8px 32px rgba(220, 53, 69, 0.3);
    }

    .toast-notification.show {
      opacity: 1;
      transform: translateY(0);
    }

    .toast-notification.hide {
      opacity: 0;
      transform: translateY(-20px);
      transition: all 0.3s ease-in-out;
    }

    /* Mobile optimizations */
    @media (max-width: 768px) {
      .toast-container {
        top: 10px;
        left: 10px;
        right: 10px;
        transform: none;
        width: auto;
      }

      .toast-notification {
        max-width: none;
        margin: 0 0 10px 0;
        font-size: 15px;
        padding: 14px 20px;
        border-radius: 10px;
      }
    }

    /* Animation for mobile touch feedback */
    @media (max-width: 480px) {
      .toast-notification {
        animation: mobileToastBounce 0.6s ease-out;
      }
    }

    @keyframes mobileToastBounce {
      0% {
        transform: translateY(-100px) scale(0.8);
        opacity: 0;
      }
      60% {
        transform: translateY(5px) scale(1.02);
        opacity: 0.9;
      }
      100% {
        transform: translateY(0) scale(1);
        opacity: 1;
      }
    }

    /* Reservation success modal */
    .modal {
      display: none;
    }
    .modal.show {
      display: block;
      position: fixed;
      inset: 0;
      z-index: 11100; /* above toast and confetti */
      pointer-events: auto;
    }
    .modal-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(0,0,0,0.45), rgba(0,0,0,0.6));
      backdrop-filter: blur(3px);
      opacity: 0;
      animation: fadeIn 260ms ease-out forwards;
    }

    @keyframes fadeIn {
      to { opacity: 1; }
    }

    .modal-content {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%) scale(0.98);
      background: linear-gradient(135deg, #ffffff, #fffdf8);
      color: #111;
      padding: 22px 28px;
      border-radius: 14px;
      max-width: 520px;
      width: min(92%, 520px);
      box-shadow: 0 18px 50px rgba(16,24,40,0.45);
      text-align: center;
      border: 1px solid rgba(0,0,0,0.06);
      transform-origin: center bottom;
      animation: modalPop 320ms cubic-bezier(0.2,0.9,0.2,1) forwards;
    }

    @keyframes modalPop {
      from { opacity: 0; transform: translate(-50%, -40%) scale(0.96); }
      to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
    }

    .modal-icon {
      width: 76px;
      height: 76px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg,#7f5af0,#1e90ff);
      color: #fff;
      font-size: 34px;
      margin: 0 auto 12px;
      box-shadow: 0 8px 24px rgba(31,41,55,0.18);
    }

    .modal-content h2 { margin: 4px 0 6px; font-size: 20px; }
    .modal-content p { margin: 0; color: #333; font-size: 15px }
    .modal-actions { margin-top: 16px; }
    .btn { background:linear-gradient(90deg,#6a3038,#8b3b43); color:#fff; border:none; padding:10px 16px; border-radius:10px; cursor:pointer; font-weight:600 }

    /* Responsive modal tweaks */
    @media (max-width: 480px) {
      .modal-icon { width: 64px; height:64px; font-size:28px }
      .modal-content { padding: 18px 16px; width: calc(100% - 32px); border-radius: 12px }
      .modal-content h2 { font-size: 18px }
    }

    /* Confetti canvas full-bleed but pointer-events none so it doesn't block clicks */
    #confettiCanvas {
      position: fixed;
      left: 0;
      top: 0;
      width: 100vw;
      height: 100vh;
      pointer-events: none;
      z-index: 11050; /* below modal */
      will-change: transform;
    }

    /* Toast Notification Styles */
    .toast-container {
      position: fixed;
      /* ensure toast sits below the sticky header and remains visible */
      top: calc(70px + 12px);
      left: 50%;
      transform: translateX(-50%);
      z-index: 10005;
      pointer-events: none;
    }

    .toast-notification {
      display: flex;
      align-items: center;
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      padding: 16px 20px;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
      min-width: 320px;
      max-width: 90vw;
      margin: 0 auto;
      transform: translateY(-100px);
      opacity: 0;
      transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      pointer-events: all;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .toast-notification.show {
      transform: translateY(0);
      opacity: 1;
    }

    .toast-notification.error {
      background: linear-gradient(135deg, #dc3545, #e74c3c);
    }

    .toast-content {
      display: flex;
      align-items: center;
      flex: 1;
    }

    .toast-icon {
      font-size: 24px;
      margin-right: 12px;
      font-weight: bold;
    }

    .toast-message {
      font-size: 16px;
      font-weight: 500;
      line-height: 1.4;
    }

    .toast-close {
      background: none;
      border: none;
      color: white;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
      margin-left: 12px;
      padding: 0;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: background-color 0.2s;
    }

    .toast-close:hover {
      background-color: rgba(255, 255, 255, 0.2);
    }

    /* Mobile optimizations for toast */
    @media (max-width: 768px) {
      .toast-container {
        top: 10px;
        left: 10px;
        right: 10px;
        transform: none;
      }

      .toast-notification {
        min-width: auto;
        width: 100%;
        padding: 14px 16px;
      }

      .toast-message {
        font-size: 15px;
      }

      .toast-icon {
        font-size: 20px;
        margin-right: 10px;
      }
    }
  </style>
</head>

<body>
  <!-- Toast Notification Container -->
  <div id="toastContainer" class="toast-container">
    <div id="toastNotification" class="toast-notification">
      <div class="toast-content">
        <div class="toast-icon">✓</div>
        <div class="toast-message"></div>
      </div>
      <button class="toast-close">&times;</button>
    </div>
  </div>

  <!-- Reservation Success Modal -->
  <div id="reservationModal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="reservationModalTitle">
    <div class="modal-overlay" tabindex="-1"></div>
    <div class="modal-content" role="document">
      <h2 id="reservationModalTitle">Reservation Confirmed</h2>
      <p id="reservationModalMessage">Your reservation was successful.</p>
      <div class="modal-actions">
        <button id="reservationModalClose" class="btn">Close</button>
      </div>
    </div>
  </div>

  <!-- Confetti canvas (created once, used for bursts) -->
  <canvas id="confettiCanvas" aria-hidden="true"></canvas>

  <div class="container">
    <header class="header sticky">
      <div class="logo-section">
        <img src="assets/logo.png" alt="Logo" class="logo" />
      </div>
      <div class="icons">
        <div class="icon facebook"><a href="https://www.facebook.com/HaveliBanquetSlough#"><i
              class="fab fa-facebook-f"></i></a></div>
        <div class="icon instagram"><a href="https://www.instagram.com/haveli_banqueting"><i
              class="fab fa-instagram"></i></a></div>
        <div class="icon cart-icon" id="cartIcon">
          <a href="#" onclick="event.preventDefault(); openCartModal()">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart-item-count"><?php echo getCartItemCount(); ?></span>
          </a>
        </div>
        <style>
          @media (max-width: 435px) {

            .icon.facebook,
            .icon.instagram {
              display: none !important;
            }
          }
        </style>
        <div class="menu-icon" id="menuToggle">
          <span class="line1"></span>
          <span class="line2"></span>
          <span class="line3"></span>
        </div>
      </div>
    </header>

    <main>
      <div class="carousel-wrapper">
        <section class="carousel">
          <div class="carousel-images">
            <div class="carousel-overlay"></div>
            <img src="assets/slide1.jpg" class="carousel-img active" />
            <img src="assets/slide2.jpg" class="carousel-img" />
            <img src="assets/slide3.jpg" class="carousel-img" />
            <img src="assets/slide4.jpg" class="carousel-img" />
          </div>

          <div class="carousel-dots">
            <span class="dot active" data-index="0"></span>
            <span class="dot" data-index="1"></span>
            <span class="dot" data-index="2"></span>
            <span class="dot" data-index="3"></span>
          </div>
        </section>
        <div class="magnetic-area">
          <a href="#interactive-menu" class="magnetic-circle" id="shape1">Order Online</a>
          <a href="#reservations" class="magnetic-circle" id="shape2">Reservations</a>
          <a href="#catering" target="_blank" rel="noopener noreferrer" class="magnetic-circle"
            id="shape4">Catering</a>
          <a href="#" class="magnetic-circle" id="shape5" onclick="openModal()">Loyalty Cards</a>
          <a href="#parking" class="magnetic-square" id="shape3">Parking</a>
          <a href="#contact" class="magnetic-square" id="shape6">Contact Us</a>
        </div>
      </div>

      <!-- Blog 3D Slider (dynamic, uses Swiper coverflow effect) -->
      <section class="blog-slider-container" aria-label="Latest blog posts">
        <h2 style="margin:18px 0 6px 0; text-align:center;">Latest from our Blog</h2>
        <div class="swiper blog-swiper" style="padding:18px 0;">
          <div class="swiper-wrapper">
            <?php if (!empty($recent_posts_slider)): ?>
              <?php foreach ($recent_posts_slider as $post): ?>
                <?php $img = !empty($post['featured_image']) ? $post['featured_image'] : 'assets/slide1.jpg'; ?>
                <div class="swiper-slide">
                  <a href="blog_post.php?slug=<?php echo urlencode($post['slug']); ?>" style="display:block; text-decoration:none; color:inherit;">
                    <div class="slide-card">
                      <div class="slide-media" style="background-image: url('<?php echo htmlspecialchars($img); ?>');"></div>
                      <div class="slide-body">
                        <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p><?php echo htmlspecialchars(mb_substr($post['excerpt'] ?? '', 0, 120)); ?>&hellip;</p>
                      </div>
                    </div>
                  </a>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="swiper-slide">
                <div class="slide-card">
                  <div class="slide-media" style="background-image: url('assets/slide1.jpg');"></div>
                  <div class="slide-body">
                    <h3>Welcome to our Blog</h3>
                    <p>We will publish updates, recipes and features here soon.</p>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <!-- Add Pagination -->
          <div class="swiper-pagination"></div>
          <!-- Navigation -->
          <div class="swiper-button-prev" aria-label="Previous post"></div>
          <div class="swiper-button-next" aria-label="Next post"></div>
        </div>
        <style>
          /* Enhanced styling for the blog slider - centered, warm background, larger cards */
          .blog-slider-container {
            /* Reduced frame to ~50% of previous width/padding */
            max-width: 580px; /* was 1160px */
            margin: 22px auto 44px;
            padding: 14px 10px; /* halved padding */
            background: linear-gradient(180deg, #fffaf6 0%, #fffdf9 100%);
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(16,24,40,0.06);
          }

          /* Swiper container sizing */
          .blog-swiper { width: 100%; padding: 10px 0 28px; position: relative; }
          /* Ensure this Swiper doesn't inherit the full-viewport hero styles */
          .blog-swiper { height: auto !important; min-height: 0 !important; background: transparent !important; }
          .blog-slider-container { display:flex; align-items:center; justify-content:center; }
          .blog-swiper .swiper-wrapper { align-items: center; }
          .blog-swiper .swiper-slide { display:flex; align-items:center; justify-content:center; }

          /* Card design */
          .slide-card {
            /* approximately half the previous card size */
            width: 190px; /* was 380px */
            height: 260px; /* was 520px */
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(2,6,23,0.12);
            background: #ffffff;
            display:flex;
            flex-direction:column;
            transition: transform 320ms cubic-bezier(.2,.9,.2,1), box-shadow 320ms;
          }
          .swiper-slide-active .slide-card { transform: translateY(-4px) scale(1.02); box-shadow: 0 20px 40px rgba(2,6,23,0.14); }

          .slide-media { height: 58%; background-size: cover; background-position: center; }
          .slide-body { padding: 16px 18px; flex:1; display:flex; flex-direction:column; }
          .slide-body h3 { margin:0 0 10px 0; font-size:20px; line-height:1.2; color:#0f172a; }
          .slide-body p { margin:0; color:#5b6370; font-size:14px; line-height:1.5; flex:1; }

          /* Navigation buttons - larger and visible */
          .blog-swiper .swiper-button-prev, .blog-swiper .swiper-button-next {
            /* half-size controls for the smaller frame */
            width:24px; height:24px; border-radius:50%; background: rgba(255,255,255,0.98); color:#0f172a;
            display:flex; align-items:center; justify-content:center; box-shadow: 0 8px 20px rgba(2,6,23,0.12);
            top: 50%; transform: translateY(-50%); z-index: 20;
          }
          .blog-swiper .swiper-button-prev { left: 6px; }
          .blog-swiper .swiper-button-next { right: 6px; }
          .blog-swiper .swiper-button-prev::after, .blog-swiper .swiper-button-next::after { font-size: 10px; }

          /* Pagination bullets */
          .blog-swiper .swiper-pagination { bottom: 6px; }
          .blog-swiper .swiper-pagination-bullet { background:#b91c1c; opacity:0.9; width:10px; height:10px; }

          /* Responsive adjustments */
          /* responsive halves */
          @media (max-width: 1100px) { .slide-card { width: 170px; height:240px; } }
          @media (max-width: 820px) { 
            .slide-card { width: 150px; height:220px; }
            .blog-slider-container { padding: 12px 8px; }
            .blog-swiper .swiper-button-prev, .blog-swiper .swiper-button-next { width:20px; height:20px; }
          }
          @media (max-width: 480px) {
            .slide-card { width: 130px; height:200px; }
            .blog-slider-container { padding: 10px 6px; border-radius: 10px; }
          }
  </style>

  <!-- Swiper JS (required for slider) -->
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

  <script>
          document.addEventListener('DOMContentLoaded', function () {
            if (typeof Swiper === 'undefined') return; // graceful

            new Swiper('.blog-swiper', {
              effect: 'coverflow',
              grabCursor: true,
              centeredSlides: true,
              loop: true,
              slidesPerView: 'auto',
              spaceBetween: 36,
              coverflowEffect: {
                rotate: 8,
                stretch: 0,
                depth: 220,
                modifier: 1,
                slideShadows: false
              },
              autoplay: {
                delay: 4200,
                disableOnInteraction: false
              },
              pagination: { el: '.blog-swiper .swiper-pagination', clickable: true },
              navigation: {
                nextEl: '.blog-swiper .swiper-button-next',
                prevEl: '.blog-swiper .swiper-button-prev'
              },
              breakpoints: {
                0: { spaceBetween: 16 },
                600: { spaceBetween: 26 },
                1100: { spaceBetween: 36 }
              }
            });
          });
        </script>
      </section>

      <section class="usp-container" id="usp">
        <h2>The Essence That Sets Us Apart</h2>
        <ul>
          <li>
            <strong>Tradition Reborn:</strong>
            We honour centuries of Indian culinary legacy while infusing it with contemporary elegance — bridging
            heritage and haute cuisine.
          </li>
          <li>
            <strong>Culinary Velocity, Never Rushed:</strong>
            Speed meets sophistication. Enjoy gourmet dishes crafted with precision, delivered with the swiftness modern
            life demands.
          </li>
          <li>
            <strong>Balanced Boldness:</strong>
            From mellow warmth to fiery boldness — our spice spectrum is tailored to every temperament, engineered to
            delight and surprise.
          </li>
          <li>
            <strong>A Place, A Pulse, A People:</strong>
            More than a dining space — Haveli is a communal experience. Vibrant, unpretentious, and alive with stories
            shared over naan.
          </li>
          <li>
            <strong>Authentically Indian, Proudly British:</strong>
            We stand at the confluence of culture — integrating India’s soul into the rhythm of British lifestyle,
            seamlessly and stylishly.
          </li>
        </ul>
      </section>

      <div class="video-background">
        <video autoplay muted loop playsinline class="bg-video">
          <source src="assets/food.mp4" type="video/mp4" />
          Your browser does not support the video tag.
        </video>
      </div>

      <section class="about-container" id="about">
        <h2>Chronicles of Our Genesis</h2>
        <div class="genesis-text">
          <div class="genesis-short">
            <p>
              Welcome to <strong>Haveli</strong> — an audacious reimagining of Indian culinary tradition, nestled amidst the cosmopolitan tapestry of the United Kingdom. Born not merely as a place of nourishment, but as an evocative journey through spice, soul, and storytelling.
            </p>
          </div>
          <div class="genesis-full" style="display:none;">
            <p>
              Here, time-honoured flavours rendezvous with contemporary finesse. We eschew the banal trappings of tired thematic decor and hackneyed theatrics; instead, we offer a sanctuary where the scent of saffron and the sizzle of tandoor speak louder than words. Rich, authentic fare — prepared with precision, served with flair.
            </p>
            
            <!-- Highlighted BBQ Feature -->
            <div class="bbq-highlight-box">
              <div class="bbq-highlight-icon"><i class="fas fa-fire"></i></div>
              <p class="bbq-highlight-text">
                <strong>First Indian Restaurant in England to Offer Self-Grill BBQ Experience</strong>
              </p>
              <p class="bbq-highlight-subtitle">Experience the unique thrill of grilling your own premium meats and vegetables at your table, guided by our expert team.</p>
            </div>
            
            <p>
              Our culinary artisans craft each dish with reverence and innovation. Signature marvels such as butter chicken, paneer tikka, and hand-layered biryanis are composed daily — not from convenience, but from conviction. We do not merely serve spice — we orchestrate it, balancing fire and flavour with symphonic mastery. From tentative tasters to daring heatseekers, Haveli invites every palate to the table.
            </p>
            <p>
              But Haveli transcends the conventional notion of a dining room. It is a gathering ground — where the pulse of local life finds rhythm in roasted cumin, where families, friends, and solitary seekers come to linger over long lunches, jubilant feasts, or soulful midweek indulgence. Every table is a story, and every bite, a chapter.
            </p>
            <p>
              Rooted in England yet resolutely Indian at its core, Haveli is not merely a restaurant. It is a movement — an evolution of identity on a plate. We are reshaping the narrative of Indian cuisine in Britain: fast without frenzy, fresh without compromise, and flavourful beyond expectation.
            </p>
            <p>
              Partake once, and you shall know: Haveli is not simply eaten. It is experienced.
            </p>
          </div>
          <button class="read-more-btn" style="display:none;">Read more</button>
        </div>
        <script>
          function isMobile() {
            return window.innerWidth <= 600;
          }
          document.addEventListener('DOMContentLoaded', function () {
            var btn = document.querySelector('.read-more-btn');
            var full = document.querySelector('.genesis-full');
            var short = document.querySelector('.genesis-short');
            if (isMobile()) {
              btn.style.display = 'block';
              btn.addEventListener('click', function () {
                full.style.display = 'block';
                btn.style.display = 'none';
              });
            } else {
              full.style.display = 'block';
              btn.style.display = 'none';
            }
          });
        </script>
      </section>
    </main>
  </div>
  </section>
  
  <!-- Sticky Mobile Menu Tabs -->
  <nav class="sticky-mobile-menu-tabs" id="stickyMobileMenuTabs">
    <div class="sticky-menu-scroll">
      <button class="tab-link active" onclick="openMenu(event, 'breakfast')">Breakfast</button>
      <button class="tab-link" onclick="openMenu(event, 'brunch')">Brunch</button>
      <button class="tab-link" onclick="openMenu(event, 'quickbites')">Quick Bites</button>
      <button class="tab-link" onclick="openMenu(event, 'sides')">Sides</button>
      <button class="tab-link" onclick="openMenu(event, 'salads')">Salads & Bowls</button>
      <button class="tab-link" onclick="openMenu(event, 'colddrinks')">Cold Drinks</button>
    </div>
  </nav>
  
  <section id="interactive-menu" class="interactive-menu-container">
    <nav class="menu-tabs">
      <button class="tab-link active" onclick="openMenu(event, 'breakfast')">Breakfast</button>
      <button class="tab-link" onclick="openMenu(event, 'brunch')">Brunch</button>
      <button class="tab-link" onclick="openMenu(event, 'quickbites')">Quick Bites</button>
      <button class="tab-link" onclick="openMenu(event, 'sides')">Sides</button>
      <button class="tab-link" onclick="openMenu(event, 'salads')">Salads & Bowls</button>
      <button class="tab-link" onclick="openMenu(event, 'colddrinks')">Cold Drinks</button>
    </nav>

    <div id="breakfast" class="menu-content active">
      <h2>Breakfast <span class="menu-subtitle">(ALL DAY)</span></h2>
      <div class="menu-items-grid">

        <?php // PAN CAKES WITH OPTIONS (CHANGED TO CHECKBOXES)
        ?>
        <?php
        $baseItemName_Pancakes = "Pan Cakes with Cream"; // Base name without tag
        $itemTag_Pancakes = "(V)";
        $baseItemPriceStr_Pancakes = "£5.95";
        $baseItemPrice_Pancakes = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_Pancakes));
        $baseItemId_Pancakes = slugify($baseItemName_Pancakes);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_Pancakes); ?> <span class="item-tag"><?php echo $itemTag_Pancakes; ?></span></h3>
            <p class="item-price"><?php echo htmlspecialchars($baseItemPriceStr_Pancakes); ?></p>
          </div>
          <p class="item-description">Fluffy pan cakes w/ choice of Strawberry, Banana, Blueberries, Nutella, Maple syrup. 285-485 Kcal</p>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_Pancakes; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_Pancakes); ?>"
            data-base-price="<?php echo $baseItemPrice_Pancakes; ?>"
            data-item-tag="<?php echo htmlspecialchars($itemTag_Pancakes); ?>">
            Select Toppings <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item-checkbox">
              <input type="checkbox" id="pancake-strawberry-<?php echo $baseItemId_Pancakes; ?>" data-option-name-suffix="Strawberry" data-option-price-adjustment="0.00">
              <label for="pancake-strawberry-<?php echo $baseItemId_Pancakes; ?>">Strawberry</label>
            </div>
            <div class="option-item-checkbox">
              <input type="checkbox" id="pancake-banana-<?php echo $baseItemId_Pancakes; ?>" data-option-name-suffix="Banana" data-option-price-adjustment="0.00">
              <label for="pancake-banana-<?php echo $baseItemId_Pancakes; ?>">Banana</label>
            </div>
            <div class="option-item-checkbox">
              <input type="checkbox" id="pancake-blueberries-<?php echo $baseItemId_Pancakes; ?>" data-option-name-suffix="Blueberries" data-option-price-adjustment="0.00">
              <label for="pancake-blueberries-<?php echo $baseItemId_Pancakes; ?>">Blueberries</label>
            </div>
            <div class="option-item-checkbox">
              <input type="checkbox" id="pancake-nutella-<?php echo $baseItemId_Pancakes; ?>" data-option-name-suffix="Nutella" data-option-price-adjustment="0.00">
              <label for="pancake-nutella-<?php echo $baseItemId_Pancakes; ?>">Nutella</label>
            </div>
            <div class="option-item-checkbox">
              <input type="checkbox" id="pancake-maple-<?php echo $baseItemId_Pancakes; ?>" data-option-name-suffix="Maple Syrup" data-option-price-adjustment="0.00">
              <label for="pancake-maple-<?php echo $baseItemId_Pancakes; ?>">Maple Syrup</label>
            </div>
            <button class="add-to-cart-from-options-btn">Add to Cart</button>
          </div>
        </div>

        <?php // YOGHURT GRANOLA BOWL - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Yoghurt Granola Bowl (V)";
        $itemPriceStr_Current = "£5.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Greek yoghurt, Granola, fresh Berries, Banana & honey. 356 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // HEALTHY PORRIDGE BOWL - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Healthy Porridge Bowl (V)";
        $itemPriceStr_Current = "£5.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Hot oatmeal porridge w/ fresh Berries, Banana & honey. 264 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // SMASHED AVOCADO TOAST - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Smashed Avocado Toast (V)";
        $itemPriceStr_Current = "£5.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Sourdough, smashed avocado, feta, pomegranate, mixed seeds. 354 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // HAVELI ENGLISH BREAKFAST - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Haveli English Breakfast";
        $itemPriceStr_Current = "£11.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Two eggs, Cumberland sausage, Bacon, Hash brown, Baked beans, Grilled tomato, Mushroom & Sourdough. 658 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // HAVELI VEG ENGLISH BREAKFAST - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Haveli Veg English Breakfast (V)";
        $itemPriceStr_Current = "£10.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Two eggs, Veg sausage, Halloumi, Hash brown, Baked beans, Grilled tomato, Mushroom & Sourdough. 625 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // BREAKFAST WRAP WITH OPTIONS (Simplified to one main choice) 
        ?>
        <?php
        $baseItemName_Wrap = "Breakfast Wrap";
        $baseItemPriceStr_Wrap = "£6.95";
        $baseItemPrice_Wrap = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_Wrap));
        $baseItemId_Wrap = slugify($baseItemName_Wrap);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_Wrap); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($baseItemPriceStr_Wrap); ?></p>
          </div>
          <p class="item-description">Tortilla wrap w/ choice of Sausage, Bacon, Egg, cheese, hash brown. 286-485 Kcal</p>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_Wrap; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_Wrap); ?>"
            data-base-price="<?php echo $baseItemPrice_Wrap; ?>">
            Select Filling <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item" data-option-name-suffix="Sausage & Egg" data-option-price-adjustment="0.00">Sausage & Egg</div>
            <div class="option-item" data-option-name-suffix="Bacon & Egg" data-option-price-adjustment="0.00">Bacon & Egg</div>
            <div class="option-item" data-option-name-suffix="Egg & Cheese (V)" data-item-tag="(V)" data-option-price-adjustment="0.00">Egg & Cheese (V)</div>
            <div class="option-item" data-option-name-suffix="Full Works" data-option-price-adjustment="1.00">Full Works (+£1.00)</div>
          </div>
        </div>

        <?php // EGGS BENEDICT - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Eggs Benedict";
        $itemPriceStr_Current = "£8.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">English muffin, ham, poached eggs, hollandaise. 478 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // EGGS ROYALE - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Eggs Royale";
        $itemPriceStr_Current = "£9.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">English muffin, Smoked salmon, poached eggs, hollandaise. 522 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // EGGS FLORENTINE - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Eggs Florentine (V)"; // Added (V) tag to name for consistency
        $itemPriceStr_Current = "£8.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">English muffin, Spinach, poached eggs, hollandaise. 439 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // OMELETTE ON TOAST - DIRECT ADD (Choices in description are for preparation, not distinct cart items unless priced differently) 
        ?>
        <?php
        $itemName_Current = "Omelette on Toast (V)";
        $itemPriceStr_Current = "£6.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Three egg Omelette w/ cheese, onion, tomato, mushroom, peppers & sourdough. 358 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // BURJI EGGS - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Burji Eggs on Sourdough Toast (V)";
        $itemPriceStr_Current = "£7.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Indian style scrambled eggs w/ onion, tomatoes, spices on sourdough. 428 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // PRANTHA WITH OPTIONS (UPDATED)
        ?>
        <?php
        $baseItemName_Prantha = "Prantha"; // Base name
        $itemTag_Prantha = "(V)";
        $baseItemPriceStr_Prantha = "£3.95";
        $baseItemPrice_Prantha = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_Prantha));
        $baseItemId_Prantha = slugify($baseItemName_Prantha);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_Prantha); ?> <span class="item-tag"><?php echo $itemTag_Prantha; ?></span></h3>
            <p class="item-price"><?php echo htmlspecialchars($baseItemPriceStr_Prantha); ?></p>
          </div>
          <p class="item-description">Indian flaky bread w/ plain yoghurt or pickle. Choice of filling. 350-450 Kcal each</p>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_Prantha; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_Prantha); ?>"
            data-base-price="<?php echo $baseItemPrice_Prantha; ?>"
            data-item-tag="<?php echo htmlspecialchars($itemTag_Prantha); ?>">
            Select Type <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item" data-option-name-suffix="Plain" data-option-price-adjustment="0.00">Plain</div>
            <div class="option-item" data-option-name-suffix="Aloo (Potato)" data-option-price-adjustment="0.00">Aloo (Potato)</div>
            <div class="option-item" data-option-name-suffix="Gobi (Cauliflower)" data-option-price-adjustment="0.00">Gobi (Cauliflower)</div>
            <div class="option-item" data-option-name-suffix="Onion" data-option-price-adjustment="0.00">Onion</div>
            <div class="option-item" data-option-name-suffix="Paneer" data-option-price-adjustment="0.00">Paneer</div>
            <div class="option-item" data-option-name-suffix="Mix Prantha" data-option-price-adjustment="0.00">Mix Prantha</div>
            <hr class="option-separator">
            <div class="option-item-addon">
              <input type="checkbox" id="prantha-chilly-<?php echo $baseItemId_Prantha; ?>" data-addon-name-suffix=" with Green Chilly" data-addon-price-adjustment="0.00">
              <label for="prantha-chilly-<?php echo $baseItemId_Prantha; ?>">Add Green Chilly (Optional)</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="brunch" class="menu-content">
      <h2>Brunch <span class="menu-subtitle">(11:00 - 17:00)</span></h2>
      <div class="menu-items-grid">
        <?php // BREKKIE BURRITO - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Brekkie Burrito";
        $itemPriceStr_Current = "£8.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Tortilla w/ scrambled eggs, tots, smashed avocado, cheese, chorizo, sriracha mayo; side salsa. 735 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // PAV BHAJI - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Pav Bhaji (V)";
        $itemPriceStr_Current = "£7.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Indian street food: spiced mashed vegetable curry (bhaji) w/ buttered bread roll (pav). 556 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // MINI INDIAN CURRY BOWLS - Splitting into individual items for simplicity 
        ?>
        <?php
        $curryBowlPriceStr = "£7.95";
        $curryBowlPrice = floatval(str_replace(['£', ','], ['', ''], $curryBowlPriceStr));
        // Chicken Tikka Masala
        $itemName_CTM_Rice = "Chicken Tikka Masala with Rice";
        $itemId_CTM_Rice = slugify($itemName_CTM_Rice);
        $itemName_CTM_Naan = "Chicken Tikka Masala with Naan";
        $itemId_CTM_Naan = slugify($itemName_CTM_Naan);
        // Lamb Rogan Josh
        $itemName_LRJ_Rice = "Lamb Rogan Josh with Rice";
        $itemId_LRJ_Rice = slugify($itemName_LRJ_Rice);
        $itemName_LRJ_Naan = "Lamb Rogan Josh with Naan";
        $itemId_LRJ_Naan = slugify($itemName_LRJ_Naan);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name">Mini Indian Curry Bowl Choices</h3>
            <p class="item-price"><?php echo htmlspecialchars($curryBowlPriceStr); ?> each</p>
          </div>
          <p class="item-description">Chicken tikka masala or lamb rogan josh w/ rice or naan. 620 Kcal approx.</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_CTM_Rice; ?>" data-name="<?php echo htmlspecialchars($itemName_CTM_Rice); ?>" data-price="<?php echo $curryBowlPrice; ?>">Add CTM w/ Rice <i class="fas fa-cart-plus"></i></button>
          <button class="add-to-cart-btn direct-add" style="margin-top:5px;" data-id="<?php echo $itemId_CTM_Naan; ?>" data-name="<?php echo htmlspecialchars($itemName_CTM_Naan); ?>" data-price="<?php echo $curryBowlPrice; ?>">Add CTM w/ Naan <i class="fas fa-cart-plus"></i></button>
          <button class="add-to-cart-btn direct-add" style="margin-top:5px;" data-id="<?php echo $itemId_LRJ_Rice; ?>" data-name="<?php echo htmlspecialchars($itemName_LRJ_Rice); ?>" data-price="<?php echo $curryBowlPrice; ?>">Add LRJ w/ Rice <i class="fas fa-cart-plus"></i></button>
          <button class="add-to-cart-btn direct-add" style="margin-top:5px;" data-id="<?php echo $itemId_LRJ_Naan; ?>" data-name="<?php echo htmlspecialchars($itemName_LRJ_Naan); ?>" data-price="<?php echo $curryBowlPrice; ?>">Add LRJ w/ Naan <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // MINI INDIAN VEG BOWLS - Splitting into individual items 
        ?>
        <?php
        $vegBowlPriceStr = "£6.95";
        $vegBowlPrice = floatval(str_replace(['£', ','], ['', ''], $vegBowlPriceStr));
        // Paneer Tikka Masala
        $itemName_PTM_Rice = "Paneer Tikka Masala with Rice (V)";
        $itemId_PTM_Rice = slugify($itemName_PTM_Rice);
        $itemName_PTM_Naan = "Paneer Tikka Masala with Naan (V)";
        $itemId_PTM_Naan = slugify($itemName_PTM_Naan);
        // Dal Makhani
        $itemName_DM_Rice = "Dal Makhani with Rice (V)";
        $itemId_DM_Rice = slugify($itemName_DM_Rice);
        $itemName_DM_Naan = "Dal Makhani with Naan (V)";
        $itemId_DM_Naan = slugify($itemName_DM_Naan);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name">Mini Indian Veg Bowl Choices <span class="item-tag">(V)</span></h3>
            <p class="item-price"><?php echo htmlspecialchars($vegBowlPriceStr); ?> each</p>
          </div>
          <p class="item-description">Paneer tikka masala or dal makhani w/ rice or naan. 520 Kcal approx.</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_PTM_Rice; ?>" data-name="<?php echo htmlspecialchars($itemName_PTM_Rice); ?>" data-price="<?php echo $vegBowlPrice; ?>">Add PTM w/ Rice <i class="fas fa-cart-plus"></i></button>
          <button class="add-to-cart-btn direct-add" style="margin-top:5px;" data-id="<?php echo $itemId_PTM_Naan; ?>" data-name="<?php echo htmlspecialchars($itemName_PTM_Naan); ?>" data-price="<?php echo $vegBowlPrice; ?>">Add PTM w/ Naan <i class="fas fa-cart-plus"></i></button>
          <button class="add-to-cart-btn direct-add" style="margin-top:5px;" data-id="<?php echo $itemId_DM_Rice; ?>" data-name="<?php echo htmlspecialchars($itemName_DM_Rice); ?>" data-price="<?php echo $vegBowlPrice; ?>">Add Dal Makhani w/ Rice <i class="fas fa-cart-plus"></i></button>
          <button class="add-to-cart-btn direct-add" style="margin-top:5px;" data-id="<?php echo $itemId_DM_Naan; ?>" data-name="<?php echo htmlspecialchars($itemName_DM_Naan); ?>" data-price="<?php echo $vegBowlPrice; ?>">Add Dal Makhani w/ Naan <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // CHICKEN BIRYANI - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Chicken Biryani (GF)";
        $itemPriceStr_Current = "£7.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Aromatic rice, tender chicken, spices, basmati rice, w/ raita. 492 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // CHANA BHATURE - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Chana Bhature (V)";
        $itemPriceStr_Current = "£10.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">North Indian: spicy chickpea curry (chana) w/ two fluffy deep-fried breads (bhature) & pickle. 486 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // BACON & CHEESE BURGER - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Bacon & Cheese Burger";
        $itemPriceStr_Current = "£9.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Beef patty, cheese, bacon, relish, lettuce, tomato, onion in brioche. W/ fries. 728 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // BEETROOT BURGER - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Beetroot Burger (V)";
        $itemPriceStr_Current = "£8.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Beetroot & red kidney bean patty, relish, lettuce, tomato, onion in brioche. W/ fries. 495 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // BONDI BURGER - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Bondi Burger";
        $itemPriceStr_Current = "£9.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Crispy grilled chicken, lettuce, tomato, onion, mayo, sriracha in brioche. W/ fries. 664 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // GRILLED PANINIS & BAGELS - OPTIONS (UPDATED)
        ?>
        <?php
        $baseItemName_Panini = "Grilled Panini/Bagel";
        $baseItemPriceStr_Panini = "£6.95";
        $baseItemPrice_Panini = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_Panini));
        $baseItemId_Panini = slugify("grilled-sandwich");
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_Panini); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($baseItemPriceStr_Panini); ?></p>
          </div>
          <p class="item-description">Grilled w/ cheese & choice of fillings. W/ salad garnish, crisps. 350-720 Kcal</p>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_Panini; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_Panini); ?>"
            data-base-price="<?php echo $baseItemPrice_Panini; ?>">
            Select Filling <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item" data-option-name-suffix="Ham & Cheese" data-option-price-adjustment="0.00">Ham & Cheese</div>
            <div class="option-item" data-option-name-suffix="Chicken & Pesto" data-option-price-adjustment="0.00">Chicken & Pesto</div>
            <div class="option-item" data-option-name-suffix="Bacon & Cheese" data-option-price-adjustment="0.00">Bacon & Cheese</div>
            <div class="option-item" data-option-name-suffix="Tuna Mayo & Sweetcorn" data-option-price-adjustment="0.00">Tuna Mayo & Sweetcorn</div>
            <div class="option-item" data-option-name-suffix="Halloumi (V)" data-item-tag="(V)" data-option-price-adjustment="0.00">Halloumi (V)</div>
          </div>
        </div>

        <?php // CHEESE & TOMATO PASTA - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Cheese & Tomato Pasta (V)";
        $itemPriceStr_Current = "£5.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Comforting pasta, rich tomato sauce, cheese, basil. 472 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // WHITE CHEESE & HERB PASTA - MOVED FROM QUICK BITES
        ?>
        <?php
        $itemName_Current = "White Cheese & Herb Pasta";
        $itemPriceStr_Current = "£6.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Creamy pasta, white cheese sauce, herbs, fresh basil. 498 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>
      </div>
    </div>

    <div id="quickbites" class="menu-content">
      <h2>Quick Bites</h2>
      <div class="menu-items-grid">
        <?php // PANI PURI - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Pani Puri (6pc) (V)";
        $itemPriceStr_Current = "£7.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Crispy puris w/ spicy tangy potato, chickpeas & tamarind chutney water. 228 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // FISH FRY PAKORA - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Fish Fry Pakora";
        $itemPriceStr_Current = "£9.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Fish marinated in spices, coated in crispy gram flour batter. 458 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // MIXED PAKORAS - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Mixed Pakoras (VG)";
        $itemPriceStr_Current = "£5.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Vegetable fritters (potato, onion, spinach) in seasoned gram flour batter, deep-fried. W/ chutney. 320 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // ALOO TIKKI CHAAT - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Aloo Tikki Chaat (V)";
        $itemPriceStr_Current = "£7.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Crispy aloo tikkis (spiced potato patties) w/ chutneys, yogurt, onions, sev. 428 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // SAMOSA CHAT - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Samosa Chat (V)";
        $itemPriceStr_Current = "£6.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Crushed crispy samosas w/ chutneys, yoghurt, chickpeas, spices. 420 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // MOMO WITH OPTIONS 
        ?>
        <?php
        $baseItemName_Momo = "Momo (5pc)";
        $baseItemPriceStr_Momo = "£6.95";
        $baseItemPrice_Momo = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_Momo));
        $baseItemId_Momo = slugify("momo-5pc");
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_Momo); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($baseItemPriceStr_Momo); ?></p>
          </div>
          <p class="item-description">Steamed dumplings (veg or chicken) w/ spicy dipping sauce. 392 Kcal</p>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_Momo; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_Momo); ?>"
            data-base-price="<?php echo $baseItemPrice_Momo; ?>">
            Select Type <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item" data-option-name-suffix="Veg" data-item-tag="(V)" data-option-price-adjustment="0.00">Veg (V)</div>
            <div class="option-item" data-option-name-suffix="Chicken" data-option-price-adjustment="0.00">Chicken</div>
          </div>
        </div>

        <?php // DAHI PAPDI CHAAT - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Dahi Papdi Chaat (V)"; // Corrected name from Dahi Pagdi
        $itemPriceStr_Current = "£6.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Crispy papdis, potatoes, chickpeas, chutneys, creamy yogurt, sev, coriander. 540 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // MASALA CURLY FRIES - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Masala Curly Fries (VG)";
        $itemPriceStr_Current = "£5.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Twister Fries w/ Indian spices. 325 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // SWEET POTATO FRIES - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Sweet Potato Fries (VG,GF,VO)";
        $itemPriceStr_Current = "£5.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Crispy sweet potato fries. 320 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // BEER-BATTERED ONION RINGS - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Beer-Battered Onion Rings (VG,GF)";
        $itemPriceStr_Current = "£4.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Crispy onion rings in beer batter. 342 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // SOUPS WITH OPTIONS 
        ?>
        <?php
        $baseItemName_Soup = "Soup";
        $baseItemPriceStr_Soup = "£4.95";
        $baseItemPrice_Soup = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_Soup));
        $baseItemId_Soup = slugify($baseItemName_Soup);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_Soup); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($baseItemPriceStr_Soup); ?></p>
          </div>
          <p class="item-description">Choice: Tomato Cream / Vegetable / Chicken. 204-285 Kcal</p>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_Soup; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_Soup); ?>"
            data-base-price="<?php echo $baseItemPrice_Soup; ?>">
            Select Type <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item" data-option-name-suffix="Tomato Cream" data-item-tag="(V)" data-option-price-adjustment="0.00">Tomato Cream (V)</div>
            <div class="option-item" data-option-name-suffix="Vegetable" data-item-tag="(V)" data-option-price-adjustment="0.00">Vegetable (V)</div>
            <div class="option-item" data-option-name-suffix="Chicken" data-option-price-adjustment="0.00">Chicken</div>
          </div>
        </div>
      </div>
    </div>

    <div id="sides" class="menu-content">
      <h2>Sides</h2>
      <div class="menu-items-grid">
        <?php // CUMBERLAND OR VEG SAUSAGES - OPTIONS 
        ?>
        <?php
        $baseItemName_Sausage = "Sausages";
        $baseItemPriceStr_Sausage = "£3.25";
        $baseItemPrice_Sausage = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_Sausage));
        $baseItemId_Sausage = slugify($baseItemName_Sausage);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name">Cumberland or Veg Sausages</h3>
            <p class="item-price"><?php echo htmlspecialchars($baseItemPriceStr_Sausage); ?></p>
          </div>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_Sausage; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_Sausage); ?>"
            data-base-price="<?php echo $baseItemPrice_Sausage; ?>">
            Select Type <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item" data-option-name-suffix="Cumberland" data-option-price-adjustment="0.00">Cumberland</div>
            <div class="option-item" data-option-name-suffix="Veg" data-item-tag="(V)" data-option-price-adjustment="0.00">Veg (V)</div>
          </div>
        </div>
        <?php
        // All other sides are direct add
        $sides = [
          ["Masala Baked Beans", "£2.95"],
          ["Baked Beans", "£2.50"],
          ["Hash Browns", "£2.50"],
          ["Fries", "£3.49"],
          ["Bacon", "£2.50"],
          ["Sourdough Toast", "£1.95"],
          ["Plain Toasts", "£1.95"],
          ["Butter Pav", "£2.50"],
          ["Bhatura", "£1.95"],
          ["Naan", "£1.95"],
          ["Rice Portion", "£2.25"],
          ["Pickle", "£1.50"],
          ["Veg Samosa", "£1.95"],
          ["Meat Samosa", "£2.25"]
        ];
        foreach ($sides as $side) {
          $itemName_Current = $side[0];
          $itemPriceStr_Current = $side[1];
          $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
          $itemId_Current = slugify($itemName_Current);
          echo '<div class="menu-item-card">
                        <div class="item-header">
                          <h3 class="item-name">' . htmlspecialchars($itemName_Current) . '</h3>
                          <p class="item-price">' . htmlspecialchars($itemPriceStr_Current) . '</p>
                        </div>
                        <button class="add-to-cart-btn direct-add" data-id="' . $itemId_Current . '" data-name="' . htmlspecialchars($itemName_Current) . '" data-price="' . $itemPrice_Current . '">Add to Cart <i class="fas fa-cart-plus"></i></button>
                      </div>';
        }
        ?>
      </div>
    </div>

    <div id="salads" class="menu-content">
      <h2>Salads & Bowls</h2>
      <div class="menu-items-grid">
        <?php // CAESAR SALAD - UPDATED WITH OPTIONS
        ?>
        <?php
        $baseItemName_CS = "Caesar Salad";
        $baseItemPriceStr_CS = "£6.95";
        $baseItemPrice_CS = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_CS));
        $baseItemId_CS = slugify($baseItemName_CS);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_CS); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($baseItemPriceStr_CS); ?></p>
          </div>
          <p class="item-description">Romaine lettuce, croutons, parmesan, Caesar dressing. Add a topping. 446 Kcal</p>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_CS; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_CS); ?>"
            data-base-price="<?php echo $baseItemPrice_CS; ?>">
            Select Options <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item" data-option-name-suffix="" data-option-price-adjustment="0.00">Plain</div>
            <div class="option-item" data-option-name-suffix="With Chicken" data-option-price-adjustment="0.00">With Chicken</div>
            <div class="option-item" data-option-name-suffix="With Halloumi" data-item-tag="(V)" data-option-price-adjustment="0.00">With Halloumi (V)</div>
          </div>
        </div>

        <?php // GREEN SALAD - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Green Salad (VG,GF)";
        $itemPriceStr_Current = "£5.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Mixed greens, cucumber, tomatoes, onions, light vinaigrette. 156 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>

        <?php // GREEK SALAD - DIRECT ADD 
        ?>
        <?php
        $itemName_Current = "Greek Salad (V,GF)";
        $itemPriceStr_Current = "£6.95";
        $itemPrice_Current = floatval(str_replace(['£', ','], ['', ''], $itemPriceStr_Current));
        $itemId_Current = slugify($itemName_Current);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($itemName_Current); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($itemPriceStr_Current); ?></p>
          </div>
          <p class="item-description">Cucumbers, tomatoes, olives, feta, red onions, lemon-herb dressing. 298 Kcal</p>
          <button class="add-to-cart-btn direct-add" data-id="<?php echo $itemId_Current; ?>" data-name="<?php echo htmlspecialchars($itemName_Current); ?>" data-price="<?php echo $itemPrice_Current; ?>">Add to Cart <i class="fas fa-cart-plus"></i></button>
        </div>
      </div>
    </div>

    <div id="colddrinks" class="menu-content">
      <h2>Cold Drinks</h2>
      <div class="menu-items-grid">
        <?php // FRESH SMOOTHIES WITH OPTIONS (CHANGED TO CHECKBOXES)
        ?>
        <?php
        $baseItemName_Smoothie = "Fresh Smoothie";
        $baseItemPriceStr_Smoothie = "£5.95";
        $baseItemPrice_Smoothie = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_Smoothie));
        $baseItemId_Smoothie = slugify($baseItemName_Smoothie);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_Smoothie); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($baseItemPriceStr_Smoothie); ?></p>
          </div>
          <p class="item-description">Blend your own: Mango, Strawberry, Banana, Mixed Berry. Check availability.</p>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_Smoothie; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_Smoothie); ?>"
            data-base-price="<?php echo $baseItemPrice_Smoothie; ?>">
            Select Flavors <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item-checkbox">
              <input type="checkbox" id="smoothie-mango-<?php echo $baseItemId_Smoothie; ?>" data-option-name-suffix="Mango" data-option-price-adjustment="0.00">
              <label for="smoothie-mango-<?php echo $baseItemId_Smoothie; ?>">Mango</label>
            </div>
            <div class="option-item-checkbox">
              <input type="checkbox" id="smoothie-strawberry-<?php echo $baseItemId_Smoothie; ?>" data-option-name-suffix="Strawberry" data-option-price-adjustment="0.00">
              <label for="smoothie-strawberry-<?php echo $baseItemId_Smoothie; ?>">Strawberry</label>
            </div>
            <div class="option-item-checkbox">
              <input type="checkbox" id="smoothie-banana-<?php echo $baseItemId_Smoothie; ?>" data-option-name-suffix="Banana" data-option-price-adjustment="0.00">
              <label for="smoothie-banana-<?php echo $baseItemId_Smoothie; ?>">Banana</label>
            </div>
            <div class="option-item-checkbox">
              <input type="checkbox" id="smoothie-berry-<?php echo $baseItemId_Smoothie; ?>" data-option-name-suffix="Mixed Berry" data-option-price-adjustment="0.00">
              <label for="smoothie-berry-<?php echo $baseItemId_Smoothie; ?>">Mixed Berry</label>
            </div>
            <button class="add-to-cart-from-options-btn">Add to Cart</button>
          </div>
        </div>

        <?php // ICED LATTE - WITH SYRUP OPTIONS
        ?>
        <?php
        $baseItemName_IcedLatte = "Iced Latte";
        $baseItemPriceStr_IcedLatte = "£4.19";
        $baseItemPrice_IcedLatte = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_IcedLatte));
        $baseItemId_IcedLatte = slugify($baseItemName_IcedLatte);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_IcedLatte); ?></h3>
            <p class="item-price">from <?php echo htmlspecialchars($baseItemPriceStr_IcedLatte); ?></p>
          </div>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_IcedLatte; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_IcedLatte); ?>"
            data-base-price="<?php echo $baseItemPrice_IcedLatte; ?>">
            Add to Cart <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item" data-option-name-suffix="" data-option-price-adjustment="0.00">No Syrup</div>
            <div class="option-item" data-option-name-suffix="with Vanilla" data-option-price-adjustment="0.50">Vanilla Syrup (+£0.50)</div>
            <div class="option-item" data-option-name-suffix="with Caramel" data-option-price-adjustment="0.50">Caramel Syrup (+£0.50)</div>
            <div class="option-item" data-option-name-suffix="with Hazelnut" data-option-price-adjustment="0.50">Hazelnut Syrup (+£0.50)</div>
          </div>
        </div>

        <?php // ICED BLACK - WITH SYRUP OPTIONS
        ?>
        <?php
        $baseItemName_IcedBlack = "Iced Black";
        $baseItemPriceStr_IcedBlack = "£3.50";
        $baseItemPrice_IcedBlack = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_IcedBlack));
        $baseItemId_IcedBlack = slugify($baseItemName_IcedBlack);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_IcedBlack); ?></h3>
            <p class="item-price">from <?php echo htmlspecialchars($baseItemPriceStr_IcedBlack); ?></p>
          </div>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_IcedBlack; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_IcedBlack); ?>"
            data-base-price="<?php echo $baseItemPrice_IcedBlack; ?>">
            Add to Cart <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item" data-option-name-suffix="" data-option-price-adjustment="0.00">No Syrup</div>
            <div class="option-item" data-option-name-suffix="with Vanilla" data-option-price-adjustment="0.50">Vanilla Syrup (+£0.50)</div>
            <div class="option-item" data-option-name-suffix="with Caramel" data-option-price-adjustment="0.50">Caramel Syrup (+£0.50)</div>
            <div class="option-item" data-option-name-suffix="with Hazelnut" data-option-price-adjustment="0.50">Hazelnut Syrup (+£0.50)</div>
          </div>
        </div>

        <?php // ICED CHOCOLATE - WITH SYRUP OPTIONS
        ?>
        <?php
        $baseItemName_IcedChoc = "Iced Chocolate";
        $baseItemPriceStr_IcedChoc = "£4.00";
        $baseItemPrice_IcedChoc = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_IcedChoc));
        $baseItemId_IcedChoc = slugify($baseItemName_IcedChoc);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_IcedChoc); ?></h3>
            <p class="item-price">from <?php echo htmlspecialchars($baseItemPriceStr_IcedChoc); ?></p>
          </div>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_IcedChoc; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_IcedChoc); ?>"
            data-base-price="<?php echo $baseItemPrice_IcedChoc; ?>">
            Add to Cart <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item" data-option-name-suffix="" data-option-price-adjustment="0.00">No Syrup</div>
            <div class="option-item" data-option-name-suffix="with Vanilla" data-option-price-adjustment="0.50">Vanilla Syrup (+£0.50)</div>
            <div class="option-item" data-option-name-suffix="with Caramel" data-option-price-adjustment="0.50">Caramel Syrup (+£0.50)</div>
            <div class="option-item" data-option-name-suffix="with Hazelnut" data-option-price-adjustment="0.50">Hazelnut Syrup (+£0.50)</div>
          </div>
        </div>

        <?php // ICED MOCHA - WITH SYRUP OPTIONS
        ?>
        <?php
        $baseItemName_IcedMocha = "Iced Mocha";
        $baseItemPriceStr_IcedMocha = "£4.20";
        $baseItemPrice_IcedMocha = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_IcedMocha));
        $baseItemId_IcedMocha = slugify($baseItemName_IcedMocha);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_IcedMocha); ?></h3>
            <p class="item-price">from <?php echo htmlspecialchars($baseItemPriceStr_IcedMocha); ?></p>
          </div>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_IcedMocha; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_IcedMocha); ?>"
            data-base-price="<?php echo $baseItemPrice_IcedMocha; ?>">
            Add to Cart <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item" data-option-name-suffix="" data-option-price-adjustment="0.00">No Syrup</div>
            <div class="option-item" data-option-name-suffix="with Vanilla" data-option-price-adjustment="0.50">Vanilla Syrup (+£0.50)</div>
            <div class="option-item" data-option-name-suffix="with Caramel" data-option-price-adjustment="0.50">Caramel Syrup (+£0.50)</div>
            <div class="option-item" data-option-name-suffix="with Hazelnut" data-option-price-adjustment="0.50">Hazelnut Syrup (+£0.50)</div>
          </div>
        </div>

        <?php // LASSI WITH OPTIONS 
        ?>
        <?php
        $baseItemName_Lassi = "Lassi";
        $baseItemPriceStr_Lassi = "£3.95";
        $baseItemPrice_Lassi = floatval(str_replace(['£', ','], ['', ''], $baseItemPriceStr_Lassi));
        $baseItemId_Lassi = slugify($baseItemName_Lassi);
        ?>
        <div class="menu-item-card">
          <div class="item-header">
            <h3 class="item-name"><?php echo htmlspecialchars($baseItemName_Lassi); ?></h3>
            <p class="item-price"><?php echo htmlspecialchars($baseItemPriceStr_Lassi); ?></p>
          </div>
          <p class="item-description">(Mango/Sweet/Salted/Plain)</p>
          <button class="add-to-cart-btn options-trigger"
            data-base-id="<?php echo $baseItemId_Lassi; ?>"
            data-base-name="<?php echo htmlspecialchars($baseItemName_Lassi); ?>"
            data-base-price="<?php echo $baseItemPrice_Lassi; ?>">
            Select Flavor <i class="fas fa-chevron-down"></i>
          </button>
          <div class="options-dropdown" style="display:none;">
            <div class="option-item" data-option-name-suffix="Mango" data-option-price-adjustment="0.00">Mango</div>
            <div class="option-item" data-option-name-suffix="Sweet" data-option-price-adjustment="0.00">Sweet</div>
            <div class="option-item" data-option-name-suffix="Salted" data-option-price-adjustment="0.00">Salted</div>
            <div class="option-item" data-option-name-suffix="Plain" data-option-price-adjustment="0.00">Plain</div>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Your existing menu tab script (openMenu function, etc.)
      // const screenWidth = window.innerWidth; ... (your description truncation script)
      // NOTE: The JavaScript handler for the 'add-to-cart-btn.options-trigger' and the new 'add-to-cart-from-options-btn' will need to be updated to support the checkbox functionality for Pancakes and Smoothies.
    </script>
  </section>

  <!-- Dinner Menu Download & Preview Section -->
  <section id="menu-download" class="menu-download-section">
    <div class="menu-download-container">
      <div class="menu-download-header">
        <h2 class="menu-download-title"><i class="fas fa-file-pdf"></i> Dinner Menu</h2>
        <p class="menu-download-subtitle">Download our dinner menu in PDF format or view a preview below</p>
      </div>
      
      <div class="menu-download-content">
        <!-- Download Button -->
        <div class="menu-download-box">
          <div class="download-icon">
            <i class="fas fa-file-pdf"></i>
          </div>
          <h3>Download Dinner Menu</h3>
          <p>Get our complete dinner menu as a PDF file</p>
          <a href="assets/menu.pdf" download="haveli-dinner-menu.pdf" class="download-menu-btn">
            <i class="fas fa-download"></i> Download PDF
          </a>
        </div>
        
        <!-- Preview -->
        <div class="menu-preview-box">
          <h3>Menu Preview</h3>
          <!-- PDF for Desktop -->
          <div class="menu-preview-pdf desktop-only">
            <embed src="assets/menu.pdf" type="application/pdf" width="100%" height="500px" />
          </div>
          <!-- PDF Pages Slider for Mobile -->
          <div class="menu-preview-slider mobile-only">
            <div class="pdf-page-container">
              <canvas id="pdfCanvas" style="max-width: 100%; height: auto; border-radius: 13px;"></canvas>
            </div>
            <div class="slider-controls">
              <button id="prevPage" class="slider-btn"><i class="fas fa-chevron-left"></i> Previous</button>
              <span class="page-counter"><span id="currentPage">1</span> / <span id="totalPages">1</span></span>
              <button id="nextPage" class="slider-btn">Next <i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PDF.js Library for Mobile PDF Rendering -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
      // Set up PDF.js worker
      if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
      }

      let pdfDoc = null;
      let currentPage = 1;
      const canvas = document.getElementById('pdfCanvas');
      const ctx = canvas ? canvas.getContext('2d') : null;

      async function loadPDF() {
        if (!canvas || !ctx) return;
        
        try {
          const pdf = await pdfjsLib.getDocument('assets/menu.pdf').promise;
          pdfDoc = pdf;
          document.getElementById('totalPages').textContent = pdf.numPages;
          await renderPage(1);
        } catch (error) {
          console.log('PDF loading on mobile:', error);
        }
      }

      async function renderPage(pageNumber) {
        if (!pdfDoc || !canvas || !ctx) return;
        
        try {
          const page = await pdfDoc.getPage(pageNumber);
          const viewport = page.getViewport({ scale: 2 });
          
          canvas.width = viewport.width;
          canvas.height = viewport.height;
          
          const renderContext = {
            canvasContext: ctx,
            viewport: viewport
          };
          
          await page.render(renderContext).promise;
          currentPage = pageNumber;
          document.getElementById('currentPage').textContent = currentPage;
        } catch (error) {
          console.log('Error rendering page:', error);
        }
      }

      // Mobile PDF slider controls
      const prevBtn = document.getElementById('prevPage');
      const nextBtn = document.getElementById('nextPage');

      if (prevBtn) {
        prevBtn.addEventListener('click', () => {
          if (pdfDoc && currentPage > 1) {
            renderPage(currentPage - 1);
          }
        });
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', () => {
          if (pdfDoc && currentPage < pdfDoc.numPages) {
            renderPage(currentPage + 1);
          }
        });
      }

      // Load PDF when DOM is ready
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadPDF);
      } else {
        loadPDF();
      }
    </script>

    <style>
      .menu-download-section {
        padding: 60px 20px;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        color: #fff;
      }

      .menu-download-container {
        max-width: 1200px;
        margin: 0 auto;
      }

      .menu-download-header {
        text-align: center;
        margin-bottom: 50px;
      }

      .menu-download-title {
        font-size: 2.5em;
        font-weight: 700;
        margin-bottom: 10px;
        background: linear-gradient(45deg, #ffd700, #ffed4e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      }

      .menu-download-subtitle {
        font-size: 1.1em;
        color: #ccc;
      }

      .menu-download-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        align-items: center;
      }

      .menu-download-box {
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid #ffd700;
        border-radius: 15px;
        padding: 40px;
        text-align: center;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
      }

      .menu-download-box:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
      }

      .download-icon {
        font-size: 3em;
        color: #ffd700;
        margin-bottom: 20px;
      }

      .menu-download-box h3 {
        font-size: 1.5em;
        margin-bottom: 10px;
      }

      .menu-download-box p {
        color: #bbb;
        margin-bottom: 25px;
      }

      .download-menu-btn {
        display: inline-block;
        padding: 12px 30px;
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        color: #000;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
      }

      .download-menu-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4);
      }

      .menu-preview-box {
        text-align: center;
      }

      .menu-preview-box h3 {
        font-size: 1.5em;
        margin-bottom: 20px;
      }

      .menu-preview-pdf {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
        background: #f5f5f5;
        border: 2px solid #e0e0e0;
      }

      .menu-preview-pdf embed {
        display: block;
        border-radius: 13px;
      }

      /* Mobile PDF Slider Styles */
      .menu-preview-slider {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
        background: #f5f5f5;
        border: 2px solid #e0e0e0;
        padding: 15px;
      }

      .pdf-page-container {
        display: flex;
        justify-content: center;
        align-items: center;
        background: #fff;
        border-radius: 10px;
        margin-bottom: 15px;
        min-height: 300px;
      }

      #pdfCanvas {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
      }

      .slider-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
      }

      .slider-btn {
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        color: #000;
        border: none;
        padding: 10px 15px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9em;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
      }

      .slider-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
      }

      .slider-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
      }

      .page-counter {
        color: #ffd700;
        font-weight: 600;
        font-size: 1em;
      }

      /* Desktop/Mobile Toggle */
      .desktop-only {
        display: block;
      }

      .mobile-only {
        display: none;
      }

      @media (max-width: 768px) {
        .desktop-only {
          display: none;
        }

        .mobile-only {
          display: block;
        }

        .menu-download-content {
          grid-template-columns: 1fr;
          gap: 30px;
        }

        .menu-download-title {
          font-size: 2em;
        }

        .menu-download-box {
          padding: 30px;
        }

        .pdf-page-container {
          min-height: 250px;
        }

        .slider-btn {
          padding: 8px 12px;
          font-size: 0.85em;
        }

        .page-counter {
          font-size: 0.9em;
        }
      }
    </style>
  </section>>

  <section id="catering" class="futuristic-catering-section">
    <div class="futuristic-container">
      <!-- Animated Background Elements -->
      <div class="futuristic-bg-elements">
        <div class="floating-particle"></div>
        <div class="floating-particle"></div>
        <div class="floating-particle"></div>
        <div class="floating-particle"></div>
        <div class="floating-particle"></div>
      </div>
      
      <!-- Holographic Grid -->
      <div class="holographic-grid"></div>
      
      <!-- Section Header -->
      <div class="futuristic-header">
        <h2 class="futuristic-title">
          <span class="neon-text">HAVELI</span>
          <span class="subtitle-glow">Private Banqueting & Catering</span>
        </h2>
        <div class="holographic-divider">
          <div class="divider-line"></div>
          <div class="divider-glow"></div>
        </div>
        <p class="futuristic-description">
          <span class="typewriter-text">Where tradition meets tomorrow. We transform every event into an otherworldly experience with cutting-edge banqueting, luxurious venues, and unforgettable celebrations.</span>
        </p>
      </div>

      <?php
      $assets_path = 'assets/';
      $venue_img = $assets_path . 'venue.jpg';

      $sections = [
        [
          'image' => 'banquet1.jpg',
          'flip' => false,
          'heading' => 'Bespoke Banquets for Every Occasion',
          'text' => 'Step into elegance with our beautifully designed banquets. Whether it’s a corporate gala or a family celebration, Haveli offers customizable spaces that radiate charm, comfort, and culture.',
        ],
        [
          'image' => 'banquet2.jpg',
          'flip' => true,
          'heading' => 'World-Class Catering That Delights',
          'text' => 'Taste the legacy of Indian flavors and global cuisine with our award-winning chefs. Our catering service includes everything from live counters to curated menus — all tailored to your event.',
        ],
        [
          'image' => 'banquet3.jpg',
          'flip' => false,
          'heading' => 'Weddings & Celebrations to Remember',
          'text' => 'Your special day deserves a venue as timeless as your love. At Haveli, we specialize in weddings, receptions, and milestone celebrations. From entrances to logistics — we handle it all.',
        ],
      ];

      foreach ($sections as $index => $section) {
        $img_path = $assets_path . $section['image'];
        $flip = $section['flip'];
        $delay = $index * 0.2;
      ?>
        <div class="futuristic-content-block <?= $flip ? 'reverse' : 'normal' ?>">
          <div class="futuristic-image-container">
            <div class="image-hologram-frame">
              <img src="<?= $img_path ?>" alt="Banquet Image" class="futuristic-image">
              <div class="hologram-overlay"></div>
              <div class="scan-lines"></div>
            </div>
          </div>
          <div class="futuristic-text-container">
            <h3 class="futuristic-heading">
              <span class="heading-glow"><?= $section['heading'] ?></span>
            </h3>
            <p class="futuristic-text">
              <?= $section['text'] ?>
            </p>
          </div>
        </div>
      <?php } ?>
      
      <!-- Futuristic to Normal Fade Transition -->
      <div class="futuristic-fade-transition">
        <div class="fade-overlay"></div>
        <div class="transition-particles">
          <div class="fade-particle"></div>
          <div class="fade-particle"></div>
          <div class="fade-particle"></div>
          <div class="fade-particle"></div>
        </div>
        <div class="transition-text">
          <span class="fade-text">Culinary Journey Begins...</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Futuristic Fade JavaScript -->
  <script>
    // Smooth fade transition based on scroll position
    window.addEventListener('scroll', function() {
      const fadeTransition = document.querySelector('.futuristic-fade-transition');
      if (fadeTransition) {
        const rect = fadeTransition.getBoundingClientRect();
        const windowHeight = window.innerHeight;
        
        // Calculate fade intensity based on visibility
        if (rect.top < windowHeight && rect.bottom > 0) {
          const visibility = Math.min(1, (windowHeight - rect.top) / windowHeight);
          const intensity = Math.max(0, Math.min(1, visibility));
          
          // Apply dynamic opacity to particles
          const particles = document.querySelectorAll('.fade-particle');
          particles.forEach((particle, index) => {
            particle.style.opacity = intensity * (0.8 - index * 0.1);
          });
          
          // Apply dynamic text glow
          const fadeText = document.querySelector('.fade-text');
          if (fadeText) {
            const glowIntensity = 10 + (intensity * 20);
            fadeText.style.textShadow = `0 0 ${glowIntensity}px #00ffff, 0 0 ${glowIntensity * 2}px #00ffff`;
          }
        }
      }
    });

    // Add intersection observer for smoother performance
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('fade-active');
          }
        });
      }, {
        threshold: 0.1,
        rootMargin: '50px'
      });

      const fadeTransition = document.querySelector('.futuristic-fade-transition');
      if (fadeTransition) {
        observer.observe(fadeTransition);
      }
    }
  </script>

      <style>
        .venue-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
          gap: 30px;
          background: url('assets/venue-bg.jpg') no-repeat center center/cover;
          padding: 80px 20px;
          position: relative;
          z-index: 1;
        }

        .venue-card {
          padding: 20px;
          border-radius: 12px;
          background: #fff;
          border: 1px solid #eee;
          box-shadow: 0 8px 22px rgba(0, 0, 0, 0.06);
          transition: transform 0.3s ease, box-shadow 0.3s ease;
          /* 👈 makes it smoooooth */
        }

        .venue-card:hover {
          transform: scale(1.05);
          box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
        }

        .venue-card h4 {
          font-size: 18px;
          margin-bottom: 8px;
          color: #222;
        }

        .venue-card p {
          font-size: 14px;
          color: #555;
          margin-bottom: 5px;
        }
      </style>

      <section id="venue-section">
        <div style="max-width: 1200px; margin: auto;">
          <h2 style="font-size: 32px; margin-bottom: 10px; text-align: center; color: #222;">Our Venues & Locations</h2>
          <div style="width: 80px; height: 4px; background: goldenrod; margin: 0 auto 30px; border-radius: 2px;"></div>
          <p style="font-size: 16px; color: #444; margin-bottom: 40px; text-align: center;">
            Explore our trusted locations across the UK, each hand-picked for luxury, accessibility, and unforgettable ambiance.
          </p>

          <div class="venue-grid">
            <?php
            $venues = [
              ['name' => 'Copthorne Hotel, Slough', 'address' => 'Cippenham Ln, Slough SL1 2YE', 'capacity' => 250],
              ['name' => 'Tudor Barn', 'address' => 'Court Ln, Britwell Rd, Burnham, Slough SL1 8DF', 'capacity' => 140],
              ['name' => 'Doubletree Hilton, Ealing', 'address' => '2-8 Hanger Lane, Ealing W5 3HN, United Kingdom', 'capacity' => 180],
              ['name' => 'Holiday Inn, Brentford', 'address' => 'Commerce Rd, London TW8 8GA', 'capacity' => 450],
              ['name' => 'Radisson Red, Heathrow', 'address' => 'Building B, Bath Rd, Heathrow Blvd, Sipson, West Drayton UB7 0DU', 'capacity' => 500],
              ['name' => 'Crowne Plaza Marlow', 'address' => 'Fieldhouse Ln, Marlow SL7 1GJ', 'capacity' => 300],
              ['name' => 'Amber Lakes', 'address' => '94a Welley Rd, Wraysbury, Staines TW19 5EP', 'capacity' => 100],
              ['name' => 'Trunkwell House', 'address' => 'Beech Hill Road, Reading RG7 2AT', 'capacity' => 350],
              ['name' => 'Thistle Hotel, Heathrow', 'address' => 'Terminal 5, Bath Rd, Longford, London UB7 0EQ', 'capacity' => 500],
              ['name' => 'Courtyard London Heathrow by Marriot', 'address' => '1 Nobel Dr, Harlington, Hayes UB3 5EY', 'capacity' => 625],
              ['name' => 'Woodlands Park Hotel', 'address' => 'Woodlands Ln, Oxshott, Cobham KT11 3QB', 'capacity' => 150],
            ];

            foreach ($venues as $venue) {
            ?>
              <div class="venue-card">
                <h4><?= $venue['name'] ?></h4>
                <p><?= $venue['address'] ?></p>
                <p><strong>Capacity:</strong> <?= $venue['capacity'] ?> guests</p>
              </div>
            <?php } ?>
          </div>
        </div>
      </section>

    </div>
  </section>
  <section id="reservations" class="reservation-section">
    <div class="reservation-container">
      <div class="reservation-header">
        <h2 class="reservation-title">Reserve Your Table</h2>
        <p class="reservation-subtitle">We can't wait to host you. All fields are required.</p>
      </div>

      <form id="reservationForm" class="reservation-form">
        <div id="reservationResponse"></div>

        <div class="form-group-clean">
          <label for="resName">Full Name</label>
          <input type="text" id="resName" name="name" required placeholder="Enter your full name">
        </div>

        <div class="form-group-clean">
          <label for="resPhone">Phone Number</label>
          <input type="tel" id="resPhone" name="phone" required placeholder="Enter your phone number">
        </div>

        <div class="form-group-clean">
          <label for="resEmail">Email</label>
          <input type="email" id="resEmail" name="email" required placeholder="Enter your email address">
        </div>

        <div class="form-row-clean">
          <div class="form-group-clean">
            <label for="resDate">Date</label>
            <input type="date" id="resDate" name="date" required>
          </div>
          <div class="form-group-clean">
            <label for="resTime">Time</label>
            <input type="time" id="resTime" name="time" required>
          </div>
        </div>

        <div class="form-group-clean">
          <label for="resGuests">Number of Guests</label>
          <select id="resGuests" name="guests" required class="custom-select">
            <option value="" disabled selected>Please select...</option>
            <?php for ($i = 1; $i <= 10; $i++): ?>
              <option value="<?php echo $i; ?>"><?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="form-group-clean">
          <button type="submit" class="submit-btn-light" style="width: 100%; margin-top: 15px;">
            <span>Request Reservation</span>
            <i class="fas fa-arrow-right"></i>
          </button>
        </div>
      </form>
    </div>
  </section>
  <section id="parking">
    <div class="parking-section">
      <div class="info-box">
        <div style="display: flex;">
          <i class="fas fa-car" style="color:#b02a2a; font-size: 100px; margin-right: 15px;"></i>
          <h2>FREE Parking</h2>
        </div>
        <p><strong>Mon–Fri:</strong> 6pm to 12 Midnight</p>
        <p><strong>Sat–Sun:</strong> All day</p>
        <p>Parking is behind the building (The Urban Building).<br>
          Press the button at the barrier and say <strong>"Haveli Lounge"</strong>. Security shall open the shutters.<br>
          Please don't forget to <strong>register your car</strong> at the reception.</p>
        <p><strong>Google Maps:</strong><br>
          For directions to our parking — search for <strong>"Haveli Lounge Parking"</strong>.<br>
          For directions to the restaurant — search for <strong>"Haveli Lounge Slough"</strong>.</p>
        <p><strong>Weekday Paid Parking Options:</strong><br>
          1. Buckingham Gardens Car Park, Slough SL1 1JF – 5 min Walk – Open 24 Hours<br>
          2. Herschel Car Park, Herschel St, Slough SL1 1XS – 7 min Walk – Closes at 10PM</p>
      </div>
    </div>
  </section>
  <section id="testimonials" class="testimonials-section">
    <div class="testimonials-container custom-slider-container">
      <h2 class="testimonials-title">What Our Customers Say</h2>

      <div class="testimonial-slider-wrapper">
        <div class="testimonial-slider">
          <div class="testimonial-slide">
            <div class="testimonial-card">
              <div class="testimonial-stars">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                  class="fas fa-star"></i><i class="fas fa-star"></i>
              </div>
              <p class="testimonial-text">"The Haveli English Breakfast is an absolute game-changer! Everything was
                cooked to perfection, and the portions were generous. Best breakfast I've had in ages!"</p>
              <p class="testimonial-author">- Sarah M.</p>
            </div>
          </div>
          <div class="testimonial-slide">
            <div class="testimonial-card">
              <div class="testimonial-stars">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                  class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
              </div>
              <p class="testimonial-text">"We tried the Chana Bhature and it was incredibly authentic and flavorful. The
                bhature were fluffy and the chana was perfectly spiced. The Mango Lassi was a delightful addition!"</p>
              <p class="testimonial-author">- David L.</p>
            </div>
          </div>
          <div class="testimonial-slide">
            <div class="testimonial-card">
              <div class="testimonial-stars">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                  class="fas fa-star"></i><i class="fas fa-star"></i>
              </div>
              <p class="testimonial-text">"Absolutely loved the Smashed Avocado Toast! So fresh and the presentation was
                beautiful. The staff were also super friendly and accommodating. A new favorite spot!"</p>
              <p class="testimonial-author">- Jessica P.</p>
            </div>
          </div>
          <div class="testimonial-slide">
            <div class="testimonial-card">
              <div class="testimonial-stars">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                  class="fas fa-star"></i><i class="far fa-star"></i>
              </div>
              <p class="testimonial-text">"The Bondi Burger was juicy and delicious, and the masala curly fries are a
                must-try! Great atmosphere for a casual meal. We'll definitely be back to try more from the menu."</p>
              <p class="testimonial-author">- Michael B.</p>
            </div>
          </div>
          <div class="testimonial-slide">
            <div class="testimonial-card">
              <div class="testimonial-stars">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                  class="fas fa-star"></i><i class="fas fa-star"></i>
              </div>
              <p class="testimonial-text">"Ordered catering for a small office event, and everyone raved about the Mini
                Indian Curry Bowls! The Chicken Tikka Masala was a hit. Professional service and great food."</p>
              <p class="testimonial-author">- Olivia C.</p>
            </div>
          </div>
          <div class="testimonial-slide">
            <div class="testimonial-card">
              <div class="testimonial-stars">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                  class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
              </div>
              <p class="testimonial-text">"The Pancakes with Nutella and banana were heavenly! A perfect treat. My kids
                loved them. The fresh smoothies are also fantastic. Great place for families."</p>
              <p class="testimonial-author">- Ethan R.</p>
            </div>
          </div>
        </div>
      </div>
      <button class="prev-btn" aria-label="Previous Testimonial"></button>
      <button class="next-btn" aria-label="Next Testimonial"></button>

      <div class="slider-pagination">
      </div>

    </div>
  </section>
  <section class="faq-section" id="faqs">
    <h2>FAQs</h2>

    <div class="faq">
      <div class="question">Do you offer vegetarian and vegan options?</div>
      <div class="answer">Yes! Haveli’s menu features a wide variety of vegetarian and vegan options full of flavour.
      </div>
    </div>

    <div class="faq">
      <div class="question">Can I reserve a table?</div>
      <div class="answer">Yes, you can call us directly or walk in. We recommend calling ahead during weekends and
        holidays.</div>
    </div>

    <div class="faq">
      <div class="question">Do you offer delivery?</div>
      <div class="answer">Yes, we are available exclusively on <strong>Uber Eats</strong> for both delivery and pickup
        orders.</div>
    </div>

    <div class="faq">
      <div class="question">Do you cater for large groups or private events?</div>
      <div class="answer">Absolutely! We cater to group bookings, private dinners, and events. Contact us in advance to
        discuss.</div>
    </div>

    <div class="faq">
      <div class="question">Are children welcome?</div>
      <div class="answer">Of course! We’re a family-friendly restaurant with kid-sized portions and high chairs
        available.</div>
    </div>

    <div class="faq">
      <div class="question">Is there parking available?</div>
      <div class="answer">Yes, we have limited parking on-site, and additional street parking is available nearby.</div>
    </div>

    <div class="faq">
      <div class="question">Do you have gluten-free options?</div>
      <div class="answer">Yes, many of our dishes are gluten-free or can be made gluten-free upon request. Just ask your
        server!</div>
    </div>

  </section>

  <section id="contact" class="contact-section-light">
    <div class="contact-container-light">
      <div class="contact-header-light">
        <h2 class="contact-title-light">Let's Connect</h2>
        <p class="contact-subtitle-light">Your feedback and inquiries are invaluable to us. Reach out, and let's start a
          conversation.</p>
      </div>
      <div class="contact-content-light">
        <div class="contact-info-light">
          <div style="display: flex; gap: 16px;">
            <!-- Phone -->
            <div class="info-card" style="flex: 1;">
              <div class="info-icon-wrapper"><i class="fas fa-phone"></i></div>
              <h4 class="info-title-light">Call Us</h4>
              <p class="info-text-light">For reservations or immediate inquiries, please call us.</p>
              <a href="tel:+441753297560" class="info-link">+44 1753 297560</a>
            </div>

            <!-- Email -->
            <div class="info-card" style="flex: 1;">
              <div class="info-icon-wrapper"><i class="fas fa-envelope"></i></div>
              <h4 class="info-title-light">Write to Us</h4>
              <p class="info-text-light">For catering and event questions, email is best.</p>
              <a href="mailto:info@haveli.co.uk" class="info-link">info@haveli.co.uk</a>
            </div>
          </div>

          <!-- Address -->
          <div class="info-card">
            <div class="info-icon-wrapper"><i class="fas fa-map-marker-alt"></i></div>
            <h4 class="info-title-light">Visit Us</h4>
            <p class="info-text-light">For visits or in-person chats, this is where you’ll find us.</p>
            <a href="https://maps.app.goo.gl/rwD56s8SvCcnrGru8" class="info-link">The Urban Building, 3-9 Albert Street, Slough, SL1 2BE</a>
          </div>
        </div>

        <div class="contact-form-wrapper-light">
          <form id="contactForm" action="contact.php" method="post" class="contact-form-light">
            <div class="form-row">
              <div class="form-group-light">
                <input type="text" name="name" id="name" required>
                <label for="name">Your Name</label>
              </div>
              <div class="form-group-light">
                <input type="email" name="email" id="email" required>
                <label for="email">Your Email</label>
              </div>
            </div>
            <div class="form-group-light">
              <textarea name="message" id="message" rows="6" required></textarea>
              <label for="message">Your Message</label>
            </div>
            <div class="form-group-light">
              <button type="submit" class="submit-btn-light">
                <span>Send Message</span>
                <i class="fas fa-arrow-right"></i>
              </button>
            </div>
          </form>
          <div class="info2-card">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2483.400830722364!2d-0.5990861231210867!3d51.50586191081541!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x48767b281f319441%3A0x536c98c9c1ca4a0b!2sHaveli%20Cafe!5e0!3m2!1sen!2sin!4v1749109259739!5m2!1sen!2sin"
              width="400" height="250" style="border:0;" allowfullscreen="" loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php if ($showThankYouModal): ?>
    <!-- HAV Thank You Modal -->
    <div id="haveliThankYouModalOverlay" class="haveli-thankyou-modal-overlay">
      <div class="haveli-thankyou-modal-card">
        <div class="haveli-thankyou-glow"></div>
        <h2>Thank you, <?php echo htmlspecialchars($customer_name); ?>! 🎉</h2>
        <p>Your order has been successfully placed. A summary is shown below:</p>
        <div class="haveli-thankyou-order-details">
          <?php echo $order_summary; ?>
        </div>
        <button onclick="closeHaveliThankYouModal()" class="haveli-thankyou-button">Back to Menu</button>
      </div>
    </div>

    <style>
      .haveli-thankyou-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(10px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 999999;
        animation: haveliFadeIn 0.4s ease-out;
      }

      .haveli-thankyou-modal-card {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.15);
        padding: 40px;
        max-width: 700px;
        width: 92%;
        border-radius: 20px;
        color: #fff;
        text-align: center;
        overflow: hidden;
        animation: haveliSlideUp 0.5s ease-out;
        box-shadow: 0 0 40px rgba(255, 255, 255, 0.2);
      }

      .haveli-thankyou-modal-card h2 {
        font-size: 2.2rem;
        margin-bottom: 15px;
      }

      .haveli-thankyou-modal-card p {
        font-size: 1.1rem;
        margin-bottom: 25px;
      }

      .haveli-thankyou-order-details {
        text-align: left;
        background: rgba(255, 255, 255, 0.1);
        padding: 20px;
        border-radius: 12px;
        max-height: 300px;
        overflow-y: auto;
        font-size: 0.95rem;
        margin-bottom: 20px;
        white-space: pre-wrap;

        /* Hide scrollbar (cross-browser) */
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* Internet Explorer 10+ */
      }

      .haveli-thankyou-order-details::-webkit-scrollbar {
        display: none;
        /* Chrome, Safari, Opera */
      }

      .haveli-thankyou-button {
        padding: 12px 30px;
        border: none;
        border-radius: 30px;
        font-size: 1rem;
        color: #fff;
        background: linear-gradient(135deg, #f857a6, #ff5858);
        box-shadow: 0 0 18px rgba(255, 88, 88, 0.6);
        cursor: pointer;
        transition: all 0.3s ease;
      }

      .haveli-thankyou-button:hover {
        transform: scale(1.05);
        box-shadow: 0 0 30px rgba(255, 88, 88, 0.8);
      }

      @keyframes haveliFadeIn {
        from {
          opacity: 0;
        }

        to {
          opacity: 1;
        }
      }

      @keyframes haveliSlideUp {
        from {
          transform: translateY(80px);
          opacity: 0;
        }

        to {
          transform: translateY(0);
          opacity: 1;
        }
      }
    </style>

    <script>
      function closeHaveliThankYouModal() {
        document.getElementById('haveliThankYouModalOverlay').style.display = 'none';
      }
    </script>
  <?php endif; ?>

  <div id="loyaltyModal" class="modal2">
    <div class="modal-content2">
      <span class="close2" onclick="closeModal()">&times;</span>
      <h2 class="modal-title2">Loyalty Cards Are Coming Soon!</h2>
      <p class="modal-description2">Get ready for exclusive rewards, special discounts, and a whole lot more. Stay tuned
        for the official launch!</p>
    </div>
  </div>
  <div id="loadingOverlay"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 128, 255); z-index:999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999; align-items:center; justify-content:center;">
    <div class="spinner"></div>
  </div>

  <style>
    .spinner {
      border: 6px solid #f3f3f3;
      border-top: 6px solid #333;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }
  </style>

  <div id="cartModal" class="modal">
    <div class="modal-content" id="cartModalContent">
      <span class="close" onclick="closeCartModal()">&times;</span>
      <h2 class="modal-title">Your Shopping Cart</h2>
      <div id="cartItemsContainer" class="modal-description">
        <p>Your cart is empty.</p>
      </div>
      <div id="cartTotalContainer" style="margin-top: 20px; font-weight: bold;">
      </div>
      <div style="margin-top: 20px;">
        <button class="checkout-btn" id="checkoutBtn" style="display:none;">Proceed to Checkout</button>
        <button class="clear-cart-btn" id="clearCartBtn" style="display:none;">Clear Cart</button>
      </div>
    </div>
  </div>

  <div class="fullscreen-nav" id="fullscreenNav">
    <ul class="nav-links2">
      <li><a href="#" class="fullscreen-nav-link">Home</a></li>
      <li><a href="#parking" class="fullscreen-nav-link">Parking</a></li>
      <li><a href="#interactive-menu" class="fullscreen-nav-link">Order Online</a></li>
      <li><a href="#reservations" class="fullscreen-nav-link">Reservations</a></li>
      <li><a href="#" class="fullscreen-nav-link" onclick="openModal()">Loyalty Cards</a></li>
      <li><a href="#catering" class="fullscreen-nav-link">Private Banqueting & Catering</a></li>
      <li><a href="#faqs" class="fullscreen-nav-link">FAQs</a></li>
      <li><a href="#testimonials" class="fullscreen-nav-link">Testimonials</a></li>
      <li><a href="#about" class="fullscreen-nav-link">About Us</a></li>
      <li><a href="#contact" class="fullscreen-nav-link">Contact</a></li>
      <li><a href="admin_access.php" class="fullscreen-nav-link admin-link" style="color: #888; font-size: 0.9em;">Admin Login</a></li>
    </ul>
    <div class="nav-close" id="closeNav">&times;</div>
  </div>

  <footer class="footer">
    <div class="footer-container">
      <div class="footer-section about">
        <div class="footer-logo-title">
          <span class="footer-logo-bg">
            <img src="assets/logo.png" alt="Haveli Logo" />
          </span>
          <h3 class="footer-neon-title">Haveli</h3>
        </div>
        <style>
          .footer-logo-title {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
            text-align: left;
          }

          .footer-logo-bg {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 90px;
            height: 90px;
            transform: translate(-50%, -55%);
            opacity: 0.13;
            z-index: 0;
            pointer-events: none;
            filter: blur(1px) drop-shadow(0 0 16px #ffb347);
          }

          .footer-logo-bg img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            user-select: none;
          }

          .footer-neon-title {
            position: relative;
            z-index: 1;
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
            font-size: 2.5rem;
            letter-spacing: 0.12em;
            color: #fff;
            text-shadow:
              0 0 8px #ffb347,
              0 0 16px #ffb347,
              0 0 32px #ffb347,
              0 0 48px #ffb347,
              0 0 64px #ffb347;
            animation: neon-flicker 4s infinite alternate;
            filter: brightness(1.2);
            font-weight: 800;
            text-align: left;
          }

          @keyframes neon-flicker {

            0%,
            100% {
              text-shadow:
                0 0 8px #ffb347,
                0 0 16px #ffb347,
                0 0 32px #ffb347,
                0 0 48px #ffb347,
                0 0 64px #ffb347;
              filter: brightness(1.2);
            }

            10% {
              text-shadow:
                0 0 4px #ffb347,
                0 0 8px #ffb347,
                0 0 16px #ffb347;
              filter: brightness(0.9);
            }

            20% {
              text-shadow:
                0 0 12px #ffb347,
                0 0 24px #ffb347,
                0 0 48px #ffb347;
              filter: brightness(1.4);
            }

            30% {
              text-shadow:
                0 0 8px #ffb347,
                0 0 16px #ffb347,
                0 0 32px #ffb347;
              filter: brightness(1.1);
            }

            40% {
              text-shadow:
                0 0 16px #ffb347,
                0 0 32px #ffb347,
                0 0 64px #ffb347;
              filter: brightness(1.3);
            }

            50% {
              text-shadow:
                0 0 4px #ffb347,
                0 0 8px #ffb347,
                0 0 16px #ffb347;
              filter: brightness(0.8);
            }

            60% {
              text-shadow:
                0 0 12px #ffb347,
                0 0 24px #ffb347,
                0 0 48px #ffb347;
              filter: brightness(1.5);
            }

            70% {
              text-shadow:
                0 0 8px #ffb347,
                0 0 16px #ffb347,
                0 0 32px #ffb347;
              filter: brightness(1.1);
            }

            80% {
              text-shadow:
                0 0 16px #ffb347,
                0 0 32px #ffb347,
                0 0 64px #ffb347;
              filter: brightness(1.3);
            }

            90% {
              text-shadow:
                0 0 4px #ffb347,
                0 0 8px #ffb347,
                0 0 16px #ffb347;
              filter: brightness(0.9);
            }
          }

          @media (max-width: 600px) {
            .footer-logo-bg {
              width: 60px;
              height: 60px;
            }

            .footer-neon-title {
              font-size: 1.5rem;
            }
          }
        </style>
        <p>
          Where tradition meets innovation. Serving authentic Indian flavours with a modern twist in the heart of the
          UK.
        </p>
      </div>

      <div class="footer-section links">
        <h4 class="footer-neon-title"
          style="font-size:1.3rem;margin-bottom:0.7rem;text-shadow:0 0 8px #ffb347,0 0 16px #ffb347,0 0 32px #ffb347;font-weight:700;letter-spacing:0.08em;">
          Quick Links</h4>
        <ul>
          <li>
            <a href="#hero">
              <span class="footer-link-icon shine-icon" aria-hidden="true">
                <i class="fas fa-home"></i>
              </span>
              <span style="position:relative;z-index:1;padding-left:1.8em;">Home</span>
            </a>
          </li>
          <li>
            <a href="#about">
              <span class="footer-link-icon shine-icon" aria-hidden="true">
                <i class="fas fa-user-friends"></i>
              </span>
              <span style="position:relative;z-index:1;padding-left:1.8em;">About Us</span>
            </a>
          </li>
          <li>
            <a href="#menu">
              <span class="footer-link-icon shine-icon" aria-hidden="true">
                <i class="fas fa-utensils"></i>
              </span>
              <span style="position:relative;z-index:1;padding-left:1.8em;">Menu</span>
            </a>
          </li>
          <li>
            <a href="#testimonials">
              <span class="footer-link-icon shine-icon" aria-hidden="true">
                <i class="fas fa-comment-dots"></i>
              </span>
              <span style="position:relative;z-index:1;padding-left:1.8em;">Testimonials</span>
            </a>
          </li>
          <li>
            <a href="#faqs">
              <span class="footer-link-icon shine-icon" aria-hidden="true">
                <i class="fas fa-question-circle"></i>
              </span>
              <span style="position:relative;z-index:1;padding-left:1.8em;">FAQs</span>
            </a>
          </li>
        </ul>
        <style>
          .footer-section.links ul {
            position: relative;
            padding-left: 0;
            margin: 0;
            list-style: none;
          }

          .footer-section.links li {
            position: relative;
            margin-bottom: 0.9em;
            line-height: 1.7;
          }

          .footer-section.links a {
            display: inline-block;
            position: relative;
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            padding-left: 0.2em;
            transition: color 0.2s;
          }

          .footer-section.links a:hover {
            color: #ffb347;
          }

          .footer-link-icon {
            position: absolute !important;
            left: 0.2em;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.3em;
            pointer-events: none;
            z-index: 0;
            margin-right: 0.6em;
          }

          .shine-icon i {
            position: relative;
            display: inline-block;
            background: #ffb347;
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shine-move 2.2s linear infinite;
            filter: drop-shadow(0 0 4px #ffb34788);
          }

          @keyframes shine-move {
            0% {
              background-position: 0% 50%;
            }

            100% {
              background-position: 100% 50%;
            }
          }

          @media (max-width: 600px) {
            .footer-link-icon {
              font-size: 1em;
              margin-right: 0.4em;
            }

            .footer-section.links li {
              margin-bottom: 0.5em;
            }
          }
        </style>
      </div>

      <div class="footer-section contact">
        <h4 class="footer-neon-title"
          style="font-size:1.3rem;margin-bottom:0.7rem;text-shadow:0 0 8px #ffb347,0 0 16px #ffb347,0 0 32px #ffb347;font-weight:700;letter-spacing:0.08em;">
          Contact Us</h4>
        <p>
          <span class="footer-link-icon shine-icon" aria-hidden="true">
            <i class="fas fa-envelope"></i>
          </span>
          <span style="position:relative;z-index:1;padding-left:1.8em;">info@haveli.co.uk</span>
        </p>
        <p>
          <span class="footer-link-icon shine-icon" aria-hidden="true">
            <i class="fas fa-phone"></i>
          </span>
          <span style="position:relative;z-index:1;padding-left:1.8em;">+44 1753 297560</span>
        </p>
        <p>
          <span class="footer-link-icon shine-icon" aria-hidden="true">
            <i class="fas fa-map-marker-alt" style="margin-top: -30px;"></i>
          </span>
          <span style="margin-left: 2rem;">The Urban Building</span><span>, 3-9 Albert Street, Slough, SL1 2BE</span>
        </p>
        <style>
          .footer-section.contact p {
            position: relative;
            margin-bottom: 0.9em;
            line-height: 1.7;
            color: #fff;
            transition: color 0.2s;
            cursor: pointer;
          }

          .footer-section.contact .footer-link-icon {
            left: 0.2em;
            transform: translateY(-50%);
            font-size: 1.3em;
            pointer-events: none;
            z-index: 0;
            transition: color 0.2s;
          }

          .footer-section.contact .shine-icon i {
            position: relative;
            background: #ffb347;
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shine-move 2.2s linear infinite;
            filter: drop-shadow(0 0 4px #ffb34788);
            transition: filter 0.2s;
          }

          @media (max-width: 600px) {
            .footer-section.contact .footer-link-icon {
              font-size: 1em;
              margin-right: 0.4em;
            }

            .footer-section.contact p {
              margin-bottom: 0.5em;
            }
          }
        </style>
      </div>

      <div class="footer-section social">
        <h4 class="footer-neon-title"
          style="font-size:1.3rem;margin-bottom:0.7rem;text-shadow:0 0 8px #ffb347,0 0 16px #ffb347,0 0 32px #ffb347;font-weight:700;letter-spacing:0.08em;">
          Follow Us</h4>
        <div class="social-icons">
          <a href="https://www.facebook.com/HaveliBanquetSlough#" aria-label="Facebook"
            class="social-icon twitter blue">
            <i class="fab fa-facebook-f"></i>
          </a>
          <a href="https://www.instagram.com/haveli_banqueting" aria-label="Instagram" class="social-icon twitter pink">
            <i class="fab fa-instagram"></i>
          </a>
        </div>
      </div>
    </div>
    <hr style="margin-top: 2.1rem; border-color: #ffffff33;">

    <p style="text-align: center; color: #ccc; font-size: 0.9rem; margin-top: 1rem;">
      Developed by
      <a href="https://www.linkedin.com/in/Vansh-Dwivedi" target="_blank" style="color: #ffb347; text-decoration: none;">
        Vansh Dwivedi
      </a>
    </p>
  </footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-3Y4X4MDC4M"></script>
  <script>
    // Initialize blog Swiper 3D coverflow when DOM ready
    document.addEventListener('DOMContentLoaded', function() {
      try {
        const blogSwiperEl = document.querySelector('.blog-swiper');
        if (blogSwiperEl && typeof Swiper !== 'undefined') {
          new Swiper(blogSwiperEl, {
            effect: 'coverflow',
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: 'auto',
            loop: true,
            coverflowEffect: {
              rotate: 30,
              stretch: 0,
              depth: 180,
              modifier: 1,
              slideShadows: true,
            },
            autoplay: {
              delay: 3500,
              disableOnInteraction: false,
            },
            pagination: { el: '.blog-swiper .swiper-pagination', clickable: true },
            navigation: { nextEl: '.blog-swiper .swiper-button-next', prevEl: '.blog-swiper .swiper-button-prev' },
            breakpoints: {
              320: { slidesPerView: 1, spaceBetween: 8 },
              640: { slidesPerView: 'auto', spaceBetween: 16 },
              960: { slidesPerView: 'auto', spaceBetween: 24 }
            }
          });
        }
      } catch (e) {
        console.error('Blog swiper init error', e);
      }
    });
    window.dataLayer = window.dataLayer || [];

    function gtag() {
      dataLayer.push(arguments);
    }
    gtag('js', new Date());

    gtag('config', 'G-3Y4X4MDC4M');
  </script>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const magneticElements = document.querySelectorAll(
        ".magnetic-circle, .magnetic-square"
      );

      const isMobileOrTablet = window.innerWidth <= 991;

      if (isMobileOrTablet) {
        magneticElements.forEach((el) => {
          el.style.display = "none";
        });
      }
    });
  </script>

  <script>
    const menuToggle = document.getElementById("menuToggle");
    const closeNav = document.getElementById("closeNav");
    const fullscreenNav = document.getElementById("fullscreenNav");

    menuToggle.addEventListener("click", () => {
      fullscreenNav.classList.toggle("show");
      menuToggle.classList.toggle("active");
    });

    closeNav.addEventListener("click", () => {
      fullscreenNav.classList.remove("show");
      menuToggle.classList.remove("active");
    });

    // ✅ Close nav on link click
    const navLinks = fullscreenNav.querySelectorAll("a");
    navLinks.forEach(link => {
      link.addEventListener("click", () => {
        fullscreenNav.classList.remove("show");
        menuToggle.classList.remove("active");
      });
    });
  </script>

  <script>
    const images = document.querySelectorAll(".carousel-img");
    const dots = document.querySelectorAll(".dot");
    let current = 0;

    function showSlide(index) {
      images.forEach((img) => img.classList.remove("active"));
      dots.forEach((dot) => dot.classList.remove("active"));

      images[index].classList.add("active");
      dots[index].classList.add("active");
    }

    function nextSlide() {
      current = (current + 1) % images.length;
      showSlide(current);
    }

    setInterval(nextSlide, 4000);

    dots.forEach((dot) => {
      dot.addEventListener("click", () => {
        current = parseInt(dot.dataset.index);
        showSlide(current);
      });
    });
  </script>

  <script>
    const carousel = document.querySelector(".carousel");

    window.addEventListener("scroll", () => {
      const scrollTop = window.scrollY;
      carousel.style.transform = `translateY(${scrollTop * 0.4}px)`; // Adjust speed here
    });
  </script>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const magneticElements = document.querySelectorAll(
        ".magnetic-circle, .magnetic-square"
      );

      const magneticStrength = 0.4;
      const magneticRadius = 300; // Increased attraction radius in pixels

      // Create a global mousemove listener for enhanced magnetic field
      document.addEventListener("mousemove", (event) => {
        const mouseX = event.clientX;
        const mouseY = event.clientY;

        magneticElements.forEach((element) => {
          const rect = element.getBoundingClientRect();
          const elementCenterX = rect.left + rect.width / 2;
          const elementCenterY = rect.top + rect.height / 2;

          // Calculate distance from cursor to element center
          const deltaX = mouseX - elementCenterX;
          const deltaY = mouseY - elementCenterY;
          const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

          // Only apply magnetic effect if cursor is within the magnetic radius
          if (distance < magneticRadius) {
            // Calculate magnetic force based on distance (closer = stronger)
            const forceMultiplier = (magneticRadius - distance) / magneticRadius;
            const magneticForce = magneticStrength * forceMultiplier;

            const translateX = deltaX * magneticForce;
            const translateY = deltaY * magneticForce;

            element.style.transform = `translate(${translateX}px, ${translateY}px)`;
            
            // Add subtle glow effect when in magnetic field
            element.style.boxShadow = `
              0 0 ${5 + forceMultiplier * 10}px rgba(0, 255, 255, ${0.3 + forceMultiplier * 0.2}),
              0 0 ${10 + forceMultiplier * 15}px rgba(0, 255, 255, ${0.2 + forceMultiplier * 0.1}),
              0 0 ${15 + forceMultiplier * 20}px rgba(0, 255, 255, ${0.1 + forceMultiplier * 0.1})
            `;
          } else {
            // Reset to original position and glow when outside magnetic field
            element.style.transform = "translate(0, 0)";
            element.style.boxShadow = `
              0 0 5px rgba(0, 255, 255, 0.3),
              0 0 10px rgba(0, 255, 255, 0.2),
              0 0 15px rgba(0, 255, 255, 0.1)
            `;
          }
        });
      });

      function applyMultiBorder(elementId, labelText, borderCount = 6, rotationStep = 2.5) {
        const box = document.getElementById(elementId);
        box.innerHTML = `<div class="multi-border"><span class="label-text">${labelText}</span></div>`;

        const container = box.querySelector(".multi-border");

        for (let i = 0; i < borderCount; i++) {
          const border = document.createElement("div");
          border.className = "border-layer";

          const angle = (i - Math.floor(borderCount / 4)) * rotationStep;
          border.style.transform = `rotate(${angle}deg) translate(${angle * 0.3}px, ${angle * 0.3}px)`;

          container.appendChild(border);
        }
      }

      applyMultiBorder("shape3", "Parking", 3, 9);
      applyMultiBorder("shape6", "Contact Us", 4, 9);
    });
  </script>

  <script>
    function openMenu(evt, menuName) {
      var i, menucontent, tablinks;

      // Get all elements with class="menu-content" and hide them
      menucontent = document.getElementsByClassName("menu-content");
      for (i = 0; i < menucontent.length; i++) {
        menucontent[i].style.display = "none";
        menucontent[i].classList.remove("active");
      }

      // Get all elements with class="tab-link" and remove the class "active"
      tablinks = document.getElementsByClassName("tab-link");
      for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
      }

      // Show the current tab, and add an "active" class to the button that opened the tab
      document.getElementById(menuName).style.display = "block"; // Or "grid" if you use grid for items directly on menu-content
      document.getElementById(menuName).classList.add("active");
      evt.currentTarget.classList.add("active");
      
      // Sync sticky mobile menu tabs
      var allTabLinks = document.querySelectorAll('.tab-link');
      allTabLinks.forEach(function(link) {
        if (link.onclick && link.onclick.toString().includes("'" + menuName + "'")) {
          link.classList.add('active');
        }
      });
    }

    // Initialize the default open tab
    document.addEventListener('DOMContentLoaded', () => {
      // Hide all menu-content divs first, except the one marked active in HTML
      var allMenuContents = document.getElementsByClassName("menu-content");
      var activeContentFound = false;
      for (var i = 0; i < allMenuContents.length; i++) {
        if (allMenuContents[i].classList.contains("active")) {
          allMenuContents[i].style.display = "block"; // Or "grid"
          activeContentFound = true;
        } else {
          allMenuContents[i].style.display = "none";
        }
      }
      // Fallback: if no tab is marked active in HTML, open the first one
      if (!activeContentFound && allMenuContents.length > 0) {
        allMenuContents[0].style.display = "block"; // Or "grid"
        allMenuContents[0].classList.add("active");
        if (document.getElementsByClassName("tab-link").length > 0) {
          document.getElementsByClassName("tab-link")[0].classList.add("active");
        }
      }
      
      // Sticky mobile menu tabs functionality - with CSS override
      if (window.innerWidth <= 600) {
        const stickyMenuTabs = document.getElementById('stickyMobileMenuTabs');
        
        if (stickyMenuTabs) {
          // Force hide initially
          stickyMenuTabs.style.setProperty('display', 'none', 'important');
          
          function checkStickyMenuVisibility() {
            const menuSection = document.getElementById('interactive-menu');
            const cateringSection = document.getElementById('catering');
            
            if (!menuSection || !cateringSection) {
              console.log('Sections not found');
              return;
            }
            
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const menuRect = menuSection.getBoundingClientRect();
            const cateringRect = cateringSection.getBoundingClientRect();
            const menuAbsoluteTop = scrollTop + menuRect.top;
            const cateringAbsoluteTop = scrollTop + cateringRect.top;
            
            console.log('Current scroll:', scrollTop);
            console.log('Menu absolute top:', menuAbsoluteTop);  
            console.log('Catering absolute top:', cateringAbsoluteTop);
            
            // Show only when we're past the menu section start AND before catering section
            if (scrollTop >= menuAbsoluteTop && scrollTop < cateringAbsoluteTop - 200) {
              stickyMenuTabs.style.setProperty('display', 'flex', 'important');
              console.log('SHOWING sticky menu');
            } else {
              stickyMenuTabs.style.setProperty('display', 'none', 'important');
              console.log('HIDING sticky menu');
            }
          }
          
          // Run check on scroll
          let ticking = false;
          window.addEventListener('scroll', function() {
            if (!ticking) {
              requestAnimationFrame(function() {
                checkStickyMenuVisibility();
                ticking = false;
              });
              ticking = true;
            }
          });
          
          // Initial check after page loads
          setTimeout(checkStickyMenuVisibility, 1000);
        }
      }
    });
  </script>
  <script>
    document.querySelectorAll('.faq').forEach(item => {
      item.addEventListener('click', () => {
        item.classList.toggle('open');
      });
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      console.log("Custom testimonial slider script initializing...");

      const sliderContainer = document.querySelector('.custom-slider-container');
      if (!sliderContainer) {
        console.error("Critical: Slider container element with class '.custom-slider-container' was NOT found. The script needs this to find buttons and pagination. Please check your HTML.");
        return;
      }
      console.log("Slider container ('.custom-slider-container') found:", sliderContainer);

      const slider = sliderContainer.querySelector('.testimonial-slider');
      if (!slider) {
        console.error("Critical: Slider element with class '.testimonial-slider' was NOT found inside '.custom-slider-container'. This is where the slides should be. Please check your HTML.");
        return;
      }
      console.log("Slider element ('.testimonial-slider') found:", slider);

      const slides = Array.from(slider.children);
      console.log(`Found ${slides.length} slides ('.testimonial-slide' elements).`);

      const nextButton = sliderContainer.querySelector('.next-btn');
      const prevButton = sliderContainer.querySelector('.prev-btn');
      const paginationContainer = sliderContainer.querySelector('.slider-pagination');

      if (!nextButton) console.error("Error: Next button ('.next-btn') not found inside '.custom-slider-container'.");
      if (!prevButton) console.error("Error: Previous button ('.prev-btn') not found inside '.custom-slider-container'.");
      if (!paginationContainer) console.error("Error: Pagination container ('.slider-pagination') not found inside '.custom-slider-container'.");

      if (!nextButton || !prevButton || !paginationContainer || slides.length === 0) {
        console.error("Script cannot run properly due to missing navigation/pagination elements or no slides. Please check class names and HTML structure.");
        if (nextButton) nextButton.style.display = 'none';
        if (prevButton) prevButton.style.display = 'none';
        return;
      }
      console.log("Navigation and pagination elements seem to be present.");

      let currentIndex = 0;
      const totalSlides = slides.length;

      const paginationDots = [];
      if (paginationContainer) {
        for (let i = 0; i < totalSlides; i++) {
          const dot = document.createElement('span');
          dot.classList.add('pagination-dot');
          dot.setAttribute('data-slide-index', i);
          dot.addEventListener('click', () => {
            console.log(`Pagination dot for slide ${i} clicked.`);
            goToSlide(i);
            stopAutoplay(); // Optional: Stop autoplay when user interacts with dots
            // startAutoplay(); // Optional: Restart autoplay after a delay
          });
          paginationContainer.appendChild(dot);
          paginationDots.push(dot);
        }
        console.log(`Created ${paginationDots.length} pagination dots.`);
      }

      function updateControls() {
        if (paginationDots.length > 0) {
          paginationDots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
          });
        }
      }

      function goToSlide(index) {
        console.log(`Attempting to go to slide: ${index}. Current index: ${currentIndex}. Total slides: ${totalSlides}`);
        if (totalSlides === 0) return; // Should not happen if initial checks pass

        if (index < 0) {
          index = totalSlides - 1;
        } else if (index >= totalSlides) {
          index = 0;
        }
        console.log(`Effective index after loop check: ${index}`);

        slider.style.transform = `translateX(-${index * 100}%)`;
        currentIndex = index;
        updateControls();
        console.log(`Slider transformed to show slide ${currentIndex}. Transform: ${slider.style.transform}`);
      }

      if (nextButton) {
        nextButton.addEventListener('click', () => {
          console.log("Next button clicked.");
          goToSlide(currentIndex + 1);
          stopAutoplay(); // User interacted, stop autoplay
          // startAutoplay(); // Optional: Restart autoplay after a delay
        });
      }

      if (prevButton) {
        prevButton.addEventListener('click', () => {
          console.log("Previous button clicked.");
          goToSlide(currentIndex - 1);
          stopAutoplay(); // User interacted, stop autoplay
          // startAutoplay(); // Optional: Restart autoplay after a delay
        });
      }

      // --- Autoplay ---
      let autoplayInterval = null;
      const autoplayDelay = 5000; // 5 seconds

      function startAutoplay() {
        if (totalSlides <= 1) {
          console.log("Autoplay not started: 1 or fewer slides.");
          return;
        }
        stopAutoplay(); // Clear any existing interval
        console.log(`Starting autoplay with delay: ${autoplayDelay}ms`);
        autoplayInterval = setInterval(() => {
          console.log("Autoplay: advancing to next slide.");
          goToSlide(currentIndex + 1);
        }, autoplayDelay);
      }

      function stopAutoplay() {
        if (autoplayInterval) {
          console.log("Stopping autoplay.");
          clearInterval(autoplayInterval);
          autoplayInterval = null;
        }
      }

      // Initialize slider to the first slide
      if (totalSlides > 0) {
        console.log("Initializing slider to the first slide (index 0).");
        goToSlide(0);
        startAutoplay(); // <<<<<<< AUTOPLAY IS NOW STARTED BY DEFAULT
      } else {
        console.warn("No slides to display. Slider will not initialize further.");
      }

      // Optional: Pause autoplay on hover over the slider wrapper itself
      const sliderWrapper = sliderContainer.querySelector('.testimonial-slider-wrapper');
      if (sliderWrapper) {
        sliderWrapper.addEventListener('mouseenter', () => {
          if (autoplayInterval) { // Only stop if it was running
            console.log("Mouse entered slider, pausing autoplay.");
            stopAutoplay();
          }
        });
        sliderWrapper.addEventListener('mouseleave', () => {
          console.log("Mouse left slider, restarting autoplay.");
          startAutoplay(); // Restart autoplay when mouse leaves
        });
      }
      // --- End Autoplay ---

      console.log("Custom testimonial slider script initialization complete.");
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // --- EXISTING ELEMENTS ---
      const cartModal = document.getElementById('cartModal');
      const loyaltyModal = document.getElementById('loyaltyModal');
      const cartIconElement = document.getElementById('cartIcon');
      const cartItemCountSpan = document.querySelector('.cart-item-count');
      const cartItemsContainer = document.getElementById('cartItemsContainer');
      const cartTotalContainer = document.getElementById('cartTotalContainer');
      const checkoutBtn = document.getElementById('checkoutBtn');
      const clearCartBtn = document.getElementById('clearCartBtn');

      // --- SLUGIFY JS (for client-side variant ID generation) ---
      function slugifyJS(text) {
        if (!text || typeof text.toString !== 'function') {
          return 'n-a-' + Math.random().toString(36).substring(2, 10);
        }
        let slug = text.toString().toLowerCase()
          .trim()
          .replace(/\s+/g, '-')
          .replace(/&/g, '-and-')
          .replace(/[^\w\-]+/g, '')
          .replace(/\-\-+/g, '-')
          .replace(/^-+/, '')
          .replace(/-+$/, '');
        if (slug === '') {
          return 'n-a-' + Math.random().toString(36).substring(2, 10);
        }
        return slug;
      }

      // --- MODAL HANDLING ---
      window.openCartModal = function(event) {
        if (event) event.preventDefault();
        loadCart();
        if (cartModal) cartModal.style.display = 'block';
      };
      window.closeCartModal = function() {
        if (cartModal) cartModal.style.display = 'none';
      };
      window.openModal = function() {
        if (loyaltyModal) loyaltyModal.style.display = "block";
      };
      window.closeModal = function() {
        if (loyaltyModal) loyaltyModal.style.display = "none";
      };

      window.addEventListener('click', function(event) {
        if (cartModal && event.target == cartModal) closeCartModal();
        if (loyaltyModal && event.target == loyaltyModal) closeModal();

        const isClickInsideTriggerOrDropdown = event.target.closest('.options-trigger, .options-dropdown');
        if (!isClickInsideTriggerOrDropdown) {
          document.querySelectorAll('.options-dropdown').forEach(dropdown => {
            dropdown.style.display = 'none';
          });
        }
      });

      document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
          if (cartModal && cartModal.style.display === 'block') closeCartModal();
          if (loyaltyModal && loyaltyModal.style.display === 'block') closeModal();
          document.querySelectorAll('.options-dropdown').forEach(dropdown => {
            dropdown.style.display = 'none';
          });
        }
      });

      // --- YOUR CENTRAL "ADD TO CART" FUNCTION ---
      // This is your existing function. We will call this.
      function performAddToCart(itemId, itemName, itemPrice, quantity = 1) {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('id', itemId);
        formData.append('name', itemName);
        formData.append('price', itemPrice);
        formData.append('quantity', quantity);

        fetch('cart_manager.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              updateCartDisplay(data.cart);
              updateCartIconCount(data.totalItems);
              if (cartIconElement) {
                cartIconElement.classList.add('pop');
                setTimeout(() => cartIconElement.classList.remove('pop'), 400);
              }
            } else {
              alert('Error: ' + data.message);
            }
          })
          .catch(error => console.error('Error adding to cart:', error));
      }

      // --- EVENT LISTENERS FOR ALL "ADD TO CART" & "SELECT" BUTTONS ---
      document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
          if (this.classList.contains('direct-add')) {
            e.stopPropagation();
            const id = this.dataset.id;
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            performAddToCart(id, name, price);
          } else if (this.classList.contains('options-trigger')) {
            e.stopPropagation();
            const dropdown = this.nextElementSibling;
            if (dropdown && dropdown.classList.contains('options-dropdown')) {
              document.querySelectorAll('.options-dropdown').forEach(od => {
                if (od !== dropdown) od.style.display = 'none';
              });
              dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            }
          }
        });
      });

      // --- NEW: LOGIC FOR CHECKBOX ITEMS (Pancakes, Smoothies) ---
      document.querySelectorAll('.add-to-cart-from-options-btn').forEach(button => {
        button.addEventListener('click', function(e) {
          e.stopPropagation();
          const card = this.closest('.menu-item-card');
          const triggerButton = card.querySelector('.options-trigger');
          const dropdown = this.closest('.options-dropdown');

          const baseName = triggerButton.dataset.baseName;
          const basePrice = parseFloat(triggerButton.dataset.basePrice);

          let selectedOptions = [];
          let priceAdjustment = 0;

          const checkedBoxes = card.querySelectorAll('.option-item-checkbox input:checked');

          if (checkedBoxes.length === 0) {
            alert('Please select at least one option.');
            return;
          }

          checkedBoxes.forEach(checkbox => {
            selectedOptions.push(checkbox.dataset.optionNameSuffix);
            priceAdjustment += parseFloat(checkbox.dataset.optionPriceAdjustment);
          });

          const finalName = `${baseName} (${selectedOptions.join(', ')})`;
          const finalPrice = basePrice + priceAdjustment;
          const finalId = slugifyJS(finalName);

          performAddToCart(finalId, finalName, finalPrice);

          if (dropdown) dropdown.style.display = 'none';
        });
      });


      // --- MODIFIED: EVENT LISTENER FOR SINGLE-CLICK OPTIONS (Now handles Prantha addon) ---
      document.getElementById('interactive-menu').addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('option-item')) {
          e.preventDefault();
          e.stopPropagation();

          const optionDiv = e.target;
          const dropdown = optionDiv.closest('.options-dropdown');
          const triggerButton = dropdown.previousElementSibling;

          const baseName = triggerButton.dataset.baseName;
          const basePrice = parseFloat(triggerButton.dataset.basePrice);

          const optionSuffix = optionDiv.dataset.optionNameSuffix;
          const priceAdjustment = parseFloat(optionDiv.dataset.optionPriceAdjustment);

          let finalItemName = `${baseName} - ${optionSuffix}`;
          let finalPrice = basePrice + priceAdjustment;

          // --- MODIFICATION START: Check for the green chilly addon ---
          const addonCheckbox = dropdown.querySelector('.option-item-addon input[type="checkbox"]');
          if (addonCheckbox && addonCheckbox.checked) {
            finalItemName += addonCheckbox.dataset.addonNameSuffix; // e.g., " with Green Chilly"
            finalPrice += parseFloat(addonCheckbox.dataset.addonPriceAdjustment);
          }
          // --- MODIFICATION END ---

          const finalItemId = slugifyJS(finalItemName);

          performAddToCart(finalItemId, finalItemName, finalPrice);

          dropdown.style.display = 'none';
        }
      });

      // --- ALL YOUR EXISTING CART DISPLAY AND UPDATE FUNCTIONS (Unchanged) ---
      function loadCart() {
        fetch('cart_manager.php?action=get_cart')
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              updateCartDisplay(data.cart);
              updateCartIconCount(data.totalItems);
            } else console.error("Failed to load cart:", data.message);
          })
          .catch(error => console.error('Error loading cart:', error));
      }

      function updateCartDisplay(cartData) {
        if (!cartItemsContainer || !cartTotalContainer || !checkoutBtn || !clearCartBtn) return;
        cartItemsContainer.innerHTML = '';
        let total = 0;
        const cartIsEmpty = Object.keys(cartData).length === 0;

        if (cartIsEmpty) {
          cartItemsContainer.innerHTML = '<p>Your cart is empty.</p>';
          checkoutBtn.style.display = 'none';
          clearCartBtn.style.display = 'none';
        } else {
          for (const id in cartData) {
            const item = cartData[id];
            const itemElement = document.createElement('div');
            itemElement.classList.add('cart-item');
            itemElement.innerHTML = `
                        <div class="item-info">
                            <span class="item-name">${escapeHTML(item.name)}</span><br>
                            <span class="item-price">£${parseFloat(item.price).toFixed(2)} each</span>
                        </div>
                        <div class="item-quantity">
                            Qty: <input type="number" value="${item.quantity}" min="1" data-id="${id}" class="quantity-input" style="width: 50px; text-align: center;">
                        </div>
                        <div class="item-subtotal">
                            £${(item.price * item.quantity).toFixed(2)}
                        </div>
                        <button class="remove-item-btn" data-id="${id}">× Remove</button>
                    `;
            cartItemsContainer.appendChild(itemElement);
            total += item.price * item.quantity;
          }
          checkoutBtn.style.display = 'inline-block';
          clearCartBtn.style.display = 'inline-block';
        }
        cartTotalContainer.innerHTML = `<strong>Total: £${total.toFixed(2)}</strong>`;
        addCartActionListeners();
      }

      function updateCartIconCount(totalItems) {
        if (cartItemCountSpan) cartItemCountSpan.textContent = totalItems;
      }

      function escapeHTML(str) {
        if (typeof str !== 'string') return '';
        const p = document.createElement("p");
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
      }

      function addCartActionListeners() {
        document.querySelectorAll('#cartModal .remove-item-btn').forEach(button => {
          const newButton = button.cloneNode(true);
          button.parentNode.replaceChild(newButton, button);
          newButton.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('id', this.dataset.id);
            fetch('cart_manager.php', {
                method: 'POST',
                body: formData
              })
              .then(response => response.json())
              .then(data => data.success ? loadCart() : alert('Error: ' + data.message));
          });
        });
        document.querySelectorAll('#cartModal .quantity-input').forEach(input => {
          const newInput = input.cloneNode(true);
          input.parentNode.replaceChild(newInput, input);
          newInput.addEventListener('change', function() {
            let quantity = parseInt(this.value);
            if (isNaN(quantity) || quantity < 0) quantity = 0;
            const formData = new FormData();
            formData.append('action', 'update_quantity');
            formData.append('id', this.dataset.id);
            formData.append('quantity', quantity);
            fetch('cart_manager.php', {
                method: 'POST',
                body: formData
              })
              .then(response => response.json())
              .then(data => data.success ? loadCart() : (alert('Error: ' + data.message), loadCart()));
          });
        });
      }

      if (clearCartBtn) {
        clearCartBtn.addEventListener('click', function() {
          if (confirm('Are you sure you want to clear the entire cart?')) {
            fetch('cart_manager.php?action=clear_cart')
              .then(response => response.json())
              .then(data => data.success ? loadCart() : alert('Error: ' + data.message));
          }
        });
      }

      if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => window.location.href = 'checkout.php');
      }

      // Initial update of cart icon
      fetch('cart_manager.php?action=get_cart')
        .then(response => response.json())
        .then(data => {
          if (data.success) updateCartIconCount(data.totalItems);
        });
    });
  </script>
  <script>
    // --- SMOOTH SCROLL FOR ANCHOR LINKS ---
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);

        if (targetElement) {
          targetElement.scrollIntoView({
            behavior: 'smooth'
          });
        }
      });
    });

    // --- TOAST NOTIFICATION FUNCTIONS ---
    function showToast(message, isError = false) {
      const toastContainer = document.getElementById('toastContainer');
      const toastNotification = document.getElementById('toastNotification');
      const toastMessage = toastNotification.querySelector('.toast-message');
      const toastIcon = toastNotification.querySelector('.toast-icon');
      const toastClose = toastNotification.querySelector('.toast-close');

      // Set message and icon
      toastMessage.textContent = message;
      toastIcon.textContent = isError ? '✗' : '✓';
      
      // Set appropriate styling
      if (isError) {
        toastNotification.classList.add('error');
      } else {
        toastNotification.classList.remove('error');
      }

      // Show the toast
      toastNotification.classList.add('show');

      // Auto-hide after 5 seconds
      const autoHideTimer = setTimeout(() => {
        hideToast();
      }, 5000);

      // Close button functionality
      toastClose.onclick = () => {
        clearTimeout(autoHideTimer);
        hideToast();
      };
    }

    function hideToast() {
      const toastNotification = document.getElementById('toastNotification');
      toastNotification.classList.remove('show');
    }

    // --- CONFETTI + SUCCESS MODAL ---
    function showConfettiAndModal(message) {
      try { playConfettiFromCorners(); } catch (e) { console.warn('Confetti failed:', e); }
      showReservationModal(message);
    }

    function showReservationModal(message) {
      const modal = document.getElementById('reservationModal');
      const msg = document.getElementById('reservationModalMessage');
      if (!modal || !msg) return;
      msg.textContent = message || 'Your reservation is confirmed!';
      modal.classList.add('show');
      modal.setAttribute('aria-hidden', 'false');

      const closeBtn = document.getElementById('reservationModalClose');
      const overlay = modal.querySelector('.modal-overlay');
      function hide() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
      }
      closeBtn.onclick = hide;
      overlay.onclick = hide;
      // allow Esc to close
      document.addEventListener('keydown', function escHandler(e) {
        if (e.key === 'Escape') { hide(); document.removeEventListener('keydown', escHandler); }
      });
    }

    function playConfettiFromCorners() {
      const canvas = document.getElementById('confettiCanvas');
      if (!canvas) return;
      const ctx = canvas.getContext('2d');

      // handle high-DPI screens
      const dpr = Math.max(1, window.devicePixelRatio || 1);
      let w = window.innerWidth;
      let h = window.innerHeight;
      canvas.style.width = w + 'px';
      canvas.style.height = h + 'px';
      canvas.width = Math.round(w * dpr);
      canvas.height = Math.round(h * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

      const particles = [];
      const colors = ['#ff4757','#ffa502','#ff6b81','#2ed573','#1e90ff','#7f5af0','#ffd166','#06d6a0'];

      // density & sizing: larger, fewer particles on small screens for performance
      const isMobile = w <= 480;
      const perCorner = isMobile ? 60 : 140; // bigger on desktop
      const sizeMultiplier = isMobile ? 1.6 : 1; // make confetti larger on mobile

      function spawnCorner(x,y,dirX) {
        const count = Math.round(perCorner * (Math.random()*0.6 + 0.8));
        for (let i=0;i<count;i++) {
          const sz = (Math.random()*14 + 10) * sizeMultiplier;
          particles.push({
            x: x + (Math.random()*80-40),
            y: y + (Math.random()*30-15),
            vx: ((Math.random()*4) + 2) * dirX,
            vy: -(Math.random()*9 + 8),
            size: sz,
            shape: Math.random() < 0.5 ? 'rect' : (Math.random() < 0.5 ? 'circle' : 'tri'),
            color: colors[Math.floor(Math.random()*colors.length)],
            rot: Math.random()*360,
            rotSpeed: (Math.random()*8 - 4) * 0.05,
            life: 4000 + Math.random()*1800,
            born: performance.now()
          });
        }
      }

      // spawn from bottom-left and bottom-right; make bursts feel dynamic
      spawnCorner(36, h - 8, 1);
      setTimeout(()=> spawnCorner(w - 36, h - 8, -1), 80);

      let last = performance.now();
      function frame(now) {
        const dt = now - last; last = now;
        // clear with slight alpha to produce cleaner motion
        ctx.clearRect(0,0,w, h);
        for (let i = particles.length -1; i >= 0; i--) {
          const p = particles[i];
          const age = now - p.born;
          if (age > p.life || p.y > h + 80) {
            particles.splice(i,1);
            continue;
          }
          // physics
          const gravity = isMobile ? 0.35 : 0.42;
          p.vy += gravity * (dt/16.67);
          p.x += p.vx * (dt/16.67);
          p.y += p.vy * (dt/16.67);
          p.rot += p.rotSpeed * (dt/16.67);

          ctx.save();
          ctx.translate(p.x, p.y);
          ctx.rotate(p.rot * Math.PI / 180);
          ctx.fillStyle = p.color;
          const s = p.size;
          if (p.shape === 'rect') {
            ctx.fillRect(-s/2, -s/2, s, s*0.6);
          } else if (p.shape === 'circle') {
            ctx.beginPath(); ctx.arc(0,0,s*0.42,0,Math.PI*2); ctx.fill();
          } else { // triangle
            ctx.beginPath(); ctx.moveTo(0, -s/2); ctx.lineTo(s/2, s/2); ctx.lineTo(-s/2, s/2); ctx.closePath(); ctx.fill();
          }
          ctx.restore();
        }
        if (particles.length > 0) {
          requestAnimationFrame(frame);
        } else {
          // clear fully after a short delay
          setTimeout(()=>{ ctx.clearRect(0,0,w,h); }, 220);
        }
      }
      requestAnimationFrame(frame);

      // adjust canvas on resize
      let resizeTO;
      function onResize() {
        clearTimeout(resizeTO);
        resizeTO = setTimeout(()=>{
          w = window.innerWidth; h = window.innerHeight;
          canvas.style.width = w + 'px'; canvas.style.height = h + 'px';
          canvas.width = Math.round(w * dpr); canvas.height = Math.round(h * dpr);
          ctx.setTransform(dpr,0,0,dpr,0,0);
        }, 120);
      }
      window.addEventListener('resize', onResize);
    }

    // --- RESERVATION FORM SUBMISSION LOGIC ---
    const reservationForm = document.getElementById('reservationForm');
    const reservationResponseDiv = document.getElementById('reservationResponse');

    if (reservationForm) {
      reservationForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Client-side validations: phone format, 2-hour lead time, opening hours
        const phoneInput = document.getElementById('resPhone');
        const dateInput = document.getElementById('resDate');
        const timeInput = document.getElementById('resTime');
        const phoneRaw = phoneInput ? phoneInput.value || '' : '';
        const digits = (phoneRaw.match(/\d/g) || []).length;
        if (digits < 10 || digits > 15) {
          showToast('Please enter a valid phone number (include country code if needed).', 'error');
          return;
        }

        const dateVal = dateInput ? dateInput.value : '';
        const timeVal = timeInput ? timeInput.value : '';
        if (!dateVal || !timeVal) {
          showToast('Please select a reservation date and time.', 'error');
          return;
        }

        const selected = new Date(dateVal + 'T' + timeVal);
        const now = new Date();
        const minAllowed = new Date(now.getTime() + 2 * 60 * 60 * 1000);
        if (selected < minAllowed) {
          showToast('Reservations must be made at least 2 hours in advance.', 'error');
          return;
        }

        // Opening hours: Mon-Fri 17:00-23:00, Sat-Sun 12:00-23:00
        const day = selected.getDay(); // 0 Sun .. 6 Sat
        const hhmm = selected.toTimeString().slice(0,5);
        let opens = '17:00', closes = '23:00';
        if (day === 0 || day === 6) { opens = '12:00'; }
        if (hhmm < opens || hhmm >= closes) {
          showToast('Selected time is outside of our opening hours. Please choose a different time.', 'error');
          return;
        }

        reservationResponseDiv.style.display = 'block';
        reservationResponseDiv.style.color = '#333';
        reservationResponseDiv.textContent = 'Sending request...';

        const formData = new FormData(this);

        // New, improved fetch code with better error handling
        fetch('submit_reservations.php', {
            method: 'POST',
            body: formData
          })
          .then(response => {
            // FIRST, check if the server responded with an error status (like 404 or 500)
            if (!response.ok) {
              // If the response is not ok, we create our own error to be caught by the .catch block
              // This will give us a clear message like "Error: 404 Not Found"
              throw new Error(`HTTP error! Status: ${response.status} ${response.statusText}`);
            }
            // If the response is OK (status 200), we then proceed to parse it as JSON
            return response.json();
          })
          .then(data => {
            // Hide the loading message
            reservationResponseDiv.style.display = 'none';
            
            // This part will only run if the server response was OK (status 200) AND the JSON is valid
            if (data.success) {
              // Show confetti and success modal instead of toast
              showConfettiAndModal(data.message || 'Reservation confirmed!');
              reservationForm.reset();
            } else {
              // Show error toast
              showToast('Error: ' + data.message, true);
            }
          })
          .catch(error => {
            // Hide the loading message
            reservationResponseDiv.style.display = 'none';
            
            // Show error toast
            let errorMessage = 'Error: ' + error.message;
            if (error.message.includes('404')) {
              errorMessage += ' - The reservation system is temporarily unavailable.';
            }
            showToast(errorMessage, true);

            console.error('Reservation submission error:', error);
          });

      })

      // --- MIN DATE SETTER FOR DATE PICKER ---
      const datePicker = document.getElementById('resDate');
      if (datePicker) {
        const today = new Date().toISOString().split('T')[0];
        datePicker.setAttribute('min', today);
      }
    }
  </script>
  <script>
    document.getElementById('contactForm').addEventListener('submit', async function(e) {
      e.preventDefault();

      document.getElementById("loadingOverlay").style.display = "flex";

      const name = document.getElementById('name').value.trim();
      const email = document.getElementById('email').value.trim();
      const message = document.getElementById('message').value.trim();

      const formData = new FormData();
      formData.append('name', name);
      formData.append('email', email);
      formData.append('message', message);

      try {
        const response = await fetch('contact.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          document.getElementById("loadingOverlay").style.display = "none";
          alert('Message sent successfully!');
          document.getElementById('contactForm').reset();
        } else {
          alert('Error: ' + result.message);
        }
      } catch (err) {
        alert('Something went wrong. Try again later.');
        console.error(err);
      }
    });
  </script>

  <!-- Toast Notification Container -->
  <div id="toastContainer" class="toast-container"></div>

  <!-- Automatic Email Queue Processing -->
  <script>
    // Automatically process email queue every 30 seconds
    function processEmailQueue() {
      fetch('process_email_queue.php')
        .then(response => response.json())
        .then(data => {
          if (data.processed > 0) {
            console.log(`✅ Processed ${data.processed} emails automatically`);
          }
        })
        .catch(error => {
          console.log('Email queue processing:', error);
        });
    }
    
    // Process emails on page load
    processEmailQueue();
    
    // Process emails every 30 seconds
    setInterval(processEmailQueue, 30000);
  </script>

</body>

</html>