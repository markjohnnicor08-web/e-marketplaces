<?php

session_start();

/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once __DIR__ . "/database.php";

/* =========================================================
   PREVENT BROWSER CACHE
========================================================= */

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");


/* =========================================================
   GET PRODUCTS FROM DATABASE
========================================================= */

$products = [];

$products_sql = "
    SELECT
        product_id,
        name,
        description,
        price,
        category,
        image,
        stock,
        status,
        date_posted
    FROM product
    WHERE LOWER(status) = 'active'
      AND stock > 0
    ORDER BY date_posted DESC
";

$products_result = mysqli_query($conn, $products_sql);


/* =========================================================
   PROCESS PRODUCTS
========================================================= */

if ($products_result) {

    while ($row = mysqli_fetch_assoc($products_result)) {

        /*
         * Default values
         * These columns are not necessarily in your table.
         */
        $row["condition"] = "Available";
        $row["location"]  = "MIST Campus";


        /*
         * IMAGE
         */
        $image = trim($row["image"] ?? "");

        $row["image_url"] = "";


        if ($image !== "") {

            /*
             * If database contains a complete URL
             * Example:
             * https://example.com/image.jpg
             */
            if (
                filter_var(
                    $image,
                    FILTER_VALIDATE_URL
                )
            ) {

                $row["image_url"] = $image;

            } else {

                /*
                 * Normalize path
                 */
                $clean_image = str_replace(
                    "\\",
                    "/",
                    $image
                );

                $clean_image = ltrim(
                    $clean_image,
                    "/"
                );


                /*
                 * Possible image locations
                 */
                $possible_images = [

                    $clean_image,

                    "uploads/" .
                    basename($clean_image),

                    "uploads/products/" .
                    basename($clean_image),

                    "assets/uploads/" .
                    basename($clean_image),

                    "assets/images/" .
                    basename($clean_image),

                    "images/" .
                    basename($clean_image),

                    "product_images/" .
                    basename($clean_image),

                    "product-images/" .
                    basename($clean_image),

                    "uploads/product_images/" .
                    basename($clean_image)

                ];


                /*
                 * Find actual image file
                 */
                foreach (
                    $possible_images
                    as $possible_image
                ) {

                    $server_path =
                        __DIR__ .
                        "/" .
                        $possible_image;


                    if (
                        file_exists($server_path) &&
                        is_file($server_path)
                    ) {

                        $row["image_url"] =
                            $possible_image;

                        break;

                    }

                }


                /*
                 * If no physical file was found,
                 * still try the original database value
                 * as browser URL.
                 */
                if (
                    empty($row["image_url"]) &&
                    !empty($clean_image)
                ) {

                    $row["image_url"] =
                        $clean_image;

                }

            }

        }


        $products[] = $row;

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>MIST Marketplace</title>

<style>

/* =========================================================
   RESET
========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #0d0f13;
    color: #ffffff;
    min-height: 100vh;
    padding-bottom: 0;
}


/* =========================================================
   SCROLLBAR
========================================================= */

::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #0d0f13;
}

::-webkit-scrollbar-thumb {
    background: #30343d;
    border-radius: 20px;
}

::-webkit-scrollbar-thumb:hover {
    background: #444a55;
}


/* =========================================================
   HEADER
========================================================= */

.header {
    height: 72px;
    background: #15181e;
    border-bottom: 1px solid #252932;
    display: flex;
    align-items: center;
    padding: 0 28px;
    position: sticky;
    top: 0;
    z-index: 1000;
}


/* =========================================================
   LOGO
========================================================= */

.logo {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 225px;
}

.logo-mark {
    width: 39px;
    height: 39px;
    flex-shrink: 0;
    border-radius: 10px;
    background: linear-gradient(135deg, #246bfe, #1745a8);
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 17px;
    font-weight: 800;
    box-shadow: 0 5px 18px rgba(36,107,254,.22);
}

.logo-text {
    display: flex;
    flex-direction: column;
}

.logo-text strong {
    font-size: 17px;
    letter-spacing: .3px;
}

.logo-text span {
    font-size: 9px;
    color: #7f8794;
    letter-spacing: 1.5px;
    margin-top: 2px;
}


/* =========================================================
   SEARCH
========================================================= */

.search-container {
    flex: 1;
    max-width: 620px;
    margin: 0 auto;
    position: relative;
}

.search-container input {
    width: 100%;
    height: 43px;
    border: 1px solid #2a2e37;
    border-radius: 10px;
    background: #0f1217;
    color: white;
    outline: none;
    padding: 0 45px 0 17px;
    font-size: 13px;
    transition: .25s ease;
}

.search-container input::placeholder {
    color: #676f7c;
}

.search-container input:focus {
    border-color: #246bfe;
    box-shadow: 0 0 0 3px rgba(36,107,254,.08);
}

.search-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #727a88;
    font-size: 17px;
}


/* =========================================================
   HEADER ACTIONS
========================================================= */

.header-actions {
    min-width: 225px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 9px;
}

.icon-btn {
    width: 40px;
    height: 40px;
    border: 1px solid #292d35;
    background: #1a1d24;
    color: #aeb5c1;
    border-radius: 9px;
    cursor: pointer;
    font-size: 16px;
    transition: .2s ease;
}

.icon-btn:hover {
    background: #242832;
    color: white;
    transform: translateY(-1px);
}

.login-header-btn {
    height: 40px;
    padding: 0 20px;
    border: none;
    border-radius: 9px;
    background: #246bfe;
    color: white;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: .25s ease;
}

.login-header-btn:hover {
    background: #3b7cff;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(36,107,254,.22);
}


/* =========================================================
   PAGE LAYOUT
========================================================= */

.page {
    display: flex;
    min-height: calc(100vh - 72px);
}


/* =========================================================
   DESKTOP SIDEBAR
========================================================= */

.sidebar {
    width: 245px;
    flex-shrink: 0;
    background: #111419;
    border-right: 1px solid #242830;
    padding: 25px 16px;
    position: sticky;
    top: 72px;
    height: calc(100vh - 72px);
    overflow-y: auto;
}

.sidebar-title {
    color: #6e7684;
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin: 5px 12px 10px;
}

.sidebar-menu {
    list-style: none;
    margin-bottom: 25px;
}

.sidebar-menu li {
    margin-bottom: 4px;
}

.sidebar-menu a {
    text-decoration: none;
    color: #aeb5c1;
    display: flex;
    align-items: center;
    gap: 12px;
    height: 43px;
    padding: 0 12px;
    border-radius: 9px;
    font-size: 13px;
    transition: .2s ease;
}

.sidebar-menu a:hover {
    background: #1c2027;
    color: white;
}

.sidebar-menu a.active {
    background: rgba(36,107,254,.12);
    color: #4c88ff;
    font-weight: bold;
}

.menu-icon {
    width: 21px;
    text-align: center;
    font-size: 15px;
}

.create-listing {
    width: 100%;
    height: 43px;
    border-radius: 9px;
    border: 1px solid #246bfe;
    background: rgba(36,107,254,.09);
    color: #4d89ff;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    margin-bottom: 28px;
    transition: .2s ease;
}

.create-listing:hover {
    background: #246bfe;
    color: white;
}

.category-list {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.category-list a {
    text-decoration: none;
    color: #818997;
    padding: 8px 12px;
    border-radius: 7px;
    font-size: 12px;
    transition: .2s ease;
}

.category-list a:hover {
    color: white;
    background: #1b1e25;
}


/* =========================================================
   MAIN
========================================================= */

.main {
    flex: 1;
    padding: 30px;
    min-width: 0;
}


/* =========================================================
   HERO
========================================================= */

.hero {
    min-height: 205px;
    border-radius: 15px;
    background:
        radial-gradient(
            circle at 80% 20%,
            rgba(36,107,254,.28),
            transparent 35%
        ),
        linear-gradient(
            120deg,
            #171c27,
            #10141b
        );
    border: 1px solid #292f3a;
    position: relative;
    overflow: hidden;
    padding: 35px 38px;
    display: flex;
    align-items: center;
    margin-bottom: 30px;
}

.hero::after {
    content: "";
    position: absolute;
    width: 280px;
    height: 280px;
    border: 1px solid rgba(70,125,255,.12);
    border-radius: 50%;
    right: -80px;
    top: -110px;
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 590px;
}

.hero-badge {
    display: inline-flex;
    padding: 6px 10px;
    background: rgba(36,107,254,.12);
    color: #5890ff;
    border: 1px solid rgba(36,107,254,.25);
    border-radius: 20px;
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 1.3px;
    margin-bottom: 13px;
}

.hero h1 {
    font-size: 27px;
    line-height: 1.25;
    margin-bottom: 9px;
}

.hero p {
    color: #8f98a6;
    font-size: 12px;
    line-height: 1.7;
    max-width: 500px;
}

.hero-button {
    display: inline-flex;
    margin-top: 18px;
    padding: 10px 16px;
    border-radius: 8px;
    background: #246bfe;
    color: white;
    text-decoration: none;
    font-size: 11px;
    font-weight: bold;
    transition: .2s ease;
}

.hero-button:hover {
    background: #3c7cff;
    transform: translateY(-1px);
}


/* =========================================================
   SECTION HEADER
========================================================= */

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}

.section-header h2 {
    font-size: 19px;
}

.section-header p {
    color: #707887;
    font-size: 11px;
    margin-top: 4px;
}

.view-all {
    color: #4785ff;
    text-decoration: none;
    font-size: 11px;
}


/* =========================================================
   CATEGORY PILLS
========================================================= */

.category-bar {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 4px;
    margin-bottom: 27px;
    scrollbar-width: none;
}

.category-bar::-webkit-scrollbar {
    display: none;
}

.category-pill {
    flex-shrink: 0;
    padding: 9px 14px;
    border-radius: 20px;
    background: #171a20;
    border: 1px solid #282c34;
    color: #969eab;
    font-size: 11px;
    cursor: pointer;
    transition: .2s ease;
}

.category-pill:hover,
.category-pill.active {
    background: rgba(36,107,254,.12);
    border-color: rgba(36,107,254,.4);
    color: #5790ff;
}


/* =========================================================
   PRODUCT GRID
========================================================= */

.product-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 17px;
}


/* =========================================================
   PRODUCT CARD
========================================================= */

.product-card {
    background: #15181e;
    border: 1px solid #272b34;
    border-radius: 12px;
    overflow: hidden;
    transition:
        transform .25s ease,
        border-color .25s ease,
        box-shadow .25s ease;
    min-width: 0;
}

.product-card:hover {
    transform: translateY(-4px);
    border-color: #3a414e;
    box-shadow: 0 15px 30px rgba(0,0,0,.18);
}


/* =========================================================
   PRODUCT IMAGE
========================================================= */

.product-image {
    height: 170px;
    background: #20242b;
    position: relative;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .35s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.04);
}

.image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 8px;
    color: #535b68;
    background: linear-gradient(135deg,#20242b,#181b21);
}

.image-placeholder span {
    font-size: 30px;
    opacity: .6;
}

.image-placeholder small {
    font-size: 9px;
    color: #5f6774;
}

.condition {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 2;
    background: rgba(8,10,13,.78);
    backdrop-filter: blur(6px);
    color: #d6dae0;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 5px;
    padding: 5px 7px;
    font-size: 8px;
}


/* =========================================================
   PRODUCT DETAILS
========================================================= */

.product-details {
    padding: 14px;
}

.product-category {
    color: #4d88ff;
    font-size: 8px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: .8px;
    margin-bottom: 7px;
}

.product-name {
    font-size: 13px;
    font-weight: bold;
    color: #f2f3f5;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 7px;
}

.product-price {
    color: #ffffff;
    font-size: 17px;
    font-weight: bold;
    margin-bottom: 8px;
}

.product-location {
    color: #727b89;
    font-size: 9px;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 13px;
}

.product-action {
    width: 100%;
    height: 35px;
    border: 1px solid #303640;
    border-radius: 7px;
    background: #1c2027;
    color: #c5cad2;
    font-size: 10px;
    font-weight: bold;
    cursor: pointer;
    transition: .2s ease;
}

.product-action:hover {
    background: #246bfe;
    border-color: #246bfe;
    color: white;
}


/* =========================================================
   MOBILE BOTTOM NAVIGATION
========================================================= */

.mobile-bottom-nav {
    display: none;
}


/* =========================================================
   FOOTER
========================================================= */

.footer {
    margin-top: 45px;
    padding: 22px 0;
    border-top: 1px solid #22262e;
    color: #555e6c;
    font-size: 10px;
    text-align: center;
}


/* =========================================================
   LARGE TABLET
========================================================= */

@media (max-width: 1200px) {

    .product-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 950px) {

    .sidebar {
        width: 205px;
    }

    .logo {
        min-width: 180px;
    }

    .header-actions {
        min-width: auto;
    }

    .search-container {
        margin: 0 15px;
    }

    .main {
        padding: 24px;
    }

}


/* =========================================================
   MOBILE / TABLET
========================================================= */

@media (max-width: 780px) {

    body {
        padding-bottom: 70px;
    }

    .header {
        height: 62px;
        padding: 0 12px;
    }

    .logo {
        min-width: auto;
    }

    .logo-mark {
        width: 36px;
        height: 36px;
        border-radius: 9px;
    }

    .logo-text {
        display: none;
    }

    /*
     * Hide desktop sidebar.
     * Its functions are moved to bottom navigation.
     */
    .sidebar {
        display: none;
    }

    .page {
        display: block;
        min-height: auto;
    }

    .main {
        width: 100%;
        padding: 12px;
        padding-bottom: 20px;
    }

    /*
     * Keep login visible
     */
    .header-actions {
        margin-left: auto;
    }

    .header-actions .icon-btn {
        display: flex;
        width: 36px;
        height: 36px;
        align-items: center;
        justify-content: center;
    }

    .login-header-btn {
        height: 36px;
        padding: 0 14px;
    }

    /*
     * Search moves underneath header.
     */
    .search-container {
        display: block;
        position: absolute;
        top: 70px;
        left: 12px;
        right: 12px;
        max-width: none;
        margin: 0;
        z-index: 900;
    }

    .search-container input {
        height: 42px;
        background: #15181e;
        border-color: #292e38;
        border-radius: 9px;
    }

    /*
     * Add space for mobile search.
     */
    .main {
        padding-top: 62px;
    }

    .hero {
        min-height: 190px;
        padding: 24px;
        margin-bottom: 22px;
        border-radius: 12px;
    }

    .hero h1 {
        font-size: 22px;
    }

    .hero p {
        font-size: 11px;
        line-height: 1.55;
    }

    .hero-badge {
        font-size: 8px;
        margin-bottom: 10px;
    }

    .hero-button {
        margin-top: 13px;
    }

    .section-header {
        margin-bottom: 13px;
    }

    .section-header h2 {
        font-size: 17px;
    }

    .section-header p {
        font-size: 10px;
    }

    .category-bar {
        margin-bottom: 18px;
        gap: 6px;
    }

    .category-pill {
        padding: 8px 12px;
        font-size: 10px;
    }

    /*
     * IMPORTANT:
     * Two products per row on mobile.
     */
    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
    }

    .product-card {
        border-radius: 9px;
    }

    .product-card:hover {
        transform: none;
        box-shadow: none;
    }

    .product-image {
        height: 155px;
    }

    .condition {
        top: 7px;
        left: 7px;
        padding: 4px 6px;
        font-size: 7px;
    }

    .product-details {
        padding: 10px;
    }

    .product-category {
        font-size: 7px;
        margin-bottom: 5px;
    }

    .product-name {
        font-size: 11px;
        margin-bottom: 5px;
    }

    .product-price {
        font-size: 15px;
        margin-bottom: 6px;
    }

    .product-location {
        font-size: 8px;
        margin-bottom: 9px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .product-action {
        height: 32px;
        font-size: 9px;
    }

    /*
     * MOBILE BOTTOM NAV
     */
    .mobile-bottom-nav {
        position: fixed;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        left: 0;
        right: 0;
        bottom: 0;
        height: 66px;
        background: rgba(17,20,25,.97);
        border-top: 1px solid #292e37;
        z-index: 2000;
        padding: 5px 4px env(safe-area-inset-bottom);
        box-shadow: 0 -8px 25px rgba(0,0,0,.28);
        backdrop-filter: blur(12px);
    }

    .mobile-nav-item {
        border: none;
        background: transparent;
        color: #747d8b;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 3px;
        font-size: 9px;
        cursor: pointer;
        border-radius: 8px;
    }

    .mobile-nav-item .mobile-nav-icon {
        width: 29px;
        height: 29px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        border-radius: 8px;
    }

    .mobile-nav-item.active {
        color: #4d88ff;
    }

    .mobile-nav-item.active .mobile-nav-icon {
        background: rgba(36,107,254,.12);
    }

    /*
     * Create listing button gets a special blue style.
     */
    .mobile-nav-item.sell {
        color: #ffffff;
    }

    .mobile-nav-item.sell .mobile-nav-icon {
        width: 38px;
        height: 38px;
        margin-top: -13px;
        border-radius: 50%;
        background: #246bfe;
        border: 4px solid #111419;
        box-shadow: 0 4px 12px rgba(36,107,254,.35);
        font-size: 18px;
    }

    .mobile-nav-item.sell span:last-child {
        color: #7f8794;
        margin-top: 1px;
    }

    .footer {
        margin-top: 30px;
        padding-bottom: 10px;
    }

}


/* =========================================================
   SMALL PHONE
========================================================= */

@media (max-width: 560px) {

    .header {
        height: 58px;
    }

    .logo-mark {
        width: 34px;
        height: 34px;
        font-size: 15px;
    }

    .header-actions {
        gap: 5px;
    }

    .header-actions .icon-btn {
        width: 34px;
        height: 34px;
        font-size: 14px;
    }

    .login-header-btn {
        height: 34px;
        padding: 0 11px;
        font-size: 10px;
    }

    .search-container {
        top: 65px;
        left: 10px;
        right: 10px;
    }

    .search-container input {
        height: 40px;
        font-size: 11px;
    }

    .main {
        padding: 58px 9px 15px;
    }

    .hero {
        min-height: 205px;
        padding: 20px;
        border-radius: 10px;
    }

    .hero h1 {
        font-size: 20px;
    }

    .hero p {
        font-size: 10px;
    }

    .hero-button {
        padding: 9px 13px;
        font-size: 10px;
    }

    .section-header h2 {
        font-size: 16px;
    }

    .section-header p {
        display: none;
    }

    /*
     * Keep 2 columns even on small phones.
     */
    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 7px;
    }

    .product-image {
        height: 135px;
    }

    .product-details {
        padding: 8px;
    }

    .product-category {
        font-size: 6.5px;
    }

    .product-name {
        font-size: 10px;
    }

    .product-price {
        font-size: 14px;
    }

    .product-location {
        font-size: 7px;
    }

    .product-action {
        height: 30px;
        font-size: 8px;
    }

    .mobile-bottom-nav {
        height: 64px;
    }

}


/* =========================================================
   VERY SMALL PHONE
========================================================= */

@media (max-width: 360px) {

    .product-grid {
        gap: 5px;
    }

    .product-image {
        height: 120px;
    }

    .product-details {
        padding: 7px;
    }

    .product-price {
        font-size: 13px;
    }

    .product-action {
        height: 28px;
        font-size: 7px;
    }

    .mobile-nav-item {
        font-size: 8px;
    }

}

</style>


</head>


<body>


<!-- =====================================================
     HEADER
====================================================== -->

<header class="header">


    <div class="logo">

        <div class="logo-mark">
            M
        </div>

        <div class="logo-text">

            <strong>
                MIST Marketplace
            </strong>

            <span>
                E-COMMERCE PLATFORM
            </span>

        </div>

    </div>


    <div class="search-container">

        <input
            type="text"
            id="searchInput"
            placeholder="Search products, categories, or sellers..."
            autocomplete="off"
        >

        <span class="search-icon">
            🔍
        </span>

    </div>


    <div class="header-actions">

        <button
            class="icon-btn"
            onclick="requireLogin()"
            title="Cart"
        >
            🛒
        </button>

        <button
            class="icon-btn"
            onclick="requireLogin()"
            title="Messages"
        >
            💬
        </button>

        <a
            href="login.php"
            class="login-header-btn"
        >
            Login
        </a>

    </div>

</header>



<div class="page">


    <aside class="sidebar">


        <div class="sidebar-title">
            Marketplace
        </div>


        <ul class="sidebar-menu">

            <li>

                <a
                    href="#"
                    class="active"
                >

                    <span class="menu-icon">
                        ◈
                    </span>

                    Browse All

                </a>

            </li>


            <li>

                <a
                    href="login.php"
                >

                    <span class="menu-icon">
                        👤
                    </span>

                    My Account

                </a>

            </li>

        </ul>


        <button
            class="create-listing"
            onclick="requireLogin()"
        >

            + Create New Listing

        </button>


        <div class="sidebar-title">
            Location
        </div>


        <ul class="sidebar-menu">

            <li>

                <a href="#">

                    <span class="menu-icon">
                        📍
                    </span>

                    MIST Campus

                </a>

            </li>

        </ul>


        <div class="sidebar-title">
            Categories
        </div>


        <div class="category-list">

            <a href="#">
                All Categories
            </a>

            <a href="#">
                Electronics
            </a>

            <a href="#">
                School Supplies
            </a>

            <a href="#">
                Fashion
            </a>

            <a href="#">
                Home & Living
            </a>

            <a href="#">
                Books
            </a>

            <a href="#">
                Vehicles
            </a>

            <a href="#">
                Others
            </a>

        </div>


    </aside>



    <main class="main">


        <section class="hero">

            <div class="hero-content">

                <span class="hero-badge">
                    MIST E-COMMERCE MARKETPLACE
                </span>

                <h1>
                    Buy, Sell & Discover<br>
                    within the MIST Community
                </h1>

                <p>
                    Find products, school essentials, gadgets,
                    furniture and more from the MIST marketplace.
                    Browse freely and login when you're ready to
                    make a transaction.
                </p>

                <a
                    href="login.php"
                    class="hero-button"
                >
                    Login to Get Started →
                </a>

            </div>

        </section>



        <div class="section-header">

            <div>

                <h2>
                    Explore Marketplace
                </h2>

                <p>
                    Discover the latest products available
                    in the MIST community.
                </p>

            </div>

            <a
                href="#"
                class="view-all"
            >
                View All
            </a>

        </div>



        <div class="category-bar">

            <button
                class="category-pill active"
                data-category="all"
            >
                All
            </button>

            <button
                class="category-pill"
                data-category="Electronics"
            >
                Electronics
            </button>

            <button
                class="category-pill"
                data-category="School Supplies"
            >
                School Supplies
            </button>

            <button
                class="category-pill"
                data-category="Fashion"
            >
                Fashion
            </button>

            <button
                class="category-pill"
                data-category="Home & Living"
            >
                Home & Living
            </button>

            <button
                class="category-pill"
                data-category="Books"
            >
                Books
            </button>

            <button
                class="category-pill"
                data-category="Vehicles"
            >
                Vehicles
            </button>

        </div>



        <div
            class="product-grid"
            id="productGrid"
        >


            <?php if (!empty($products)): ?>


                <?php foreach ($products as $product): ?>


                    <div
                        class="product-card"
                        data-category="<?= htmlspecialchars($product["category"] ?? "") ?>"
                        data-name="<?= htmlspecialchars(strtolower($product["name"] ?? "")) ?>"
                    >


                        <div class="product-image">


                            <span class="condition">

                                <?= htmlspecialchars(
                                    $product["condition"] ?? "Available"
                                ) ?>

                            </span>


                            <?php if (!empty($product["image_url"])): ?>

                                <img
                                    src="<?= htmlspecialchars(
                                        $product["image_url"]
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $product["name"] ?? "Product"
                                    ) ?>"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >

                                <div
                                    class="image-placeholder"
                                    style="display:none;"
                                >

                                    <span>
                                        🛍️
                                    </span>

                                    <small>
                                        Product Image
                                    </small>

                                </div>

                            <?php else: ?>

                                <div class="image-placeholder">

                                    <span>
                                        🛍️
                                    </span>

                                    <small>
                                        Product Image
                                    </small>

                                </div>

                            <?php endif; ?>


                        </div>


                        <div class="product-details">


                            <div class="product-category">

                                <?= htmlspecialchars(
                                    $product["category"] ?? "Others"
                                ) ?>

                            </div>


                            <div class="product-name">

                                <?= htmlspecialchars(
                                    $product["name"] ?? "Unnamed Product"
                                ) ?>

                            </div>


                            <div class="product-price">

                                ₱<?= number_format(
                                    (float)($product["price"] ?? 0),
                                    2
                                ) ?>

                            </div>


                            <div class="product-location">

                                📍

                                <?= htmlspecialchars(
                                    $product["location"] ?? "MIST Campus"
                                ) ?>

                            </div>


                            <button
                                class="product-action"
                                onclick="requireLogin()"
                            >

                                View Product

                            </button>


                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div
                    style="
                        grid-column: 1 / -1;
                        text-align: center;
                        padding: 60px 20px;
                        color: #707887;
                    "
                >

                    <div style="font-size:40px;">
                        🛍️
                    </div>

                    <p style="margin-top:12px;">
                        No products available yet.
                    </p>

                </div>


            <?php endif; ?>


        </div>



        <footer class="footer">

            © <?= date("Y") ?> MIST E-Commerce Marketplace.
            All rights reserved.

        </footer>


    </main>

</div>



<script>

/* =========================================================
   LOGIN REQUIRED
========================================================= */

function requireLogin() {

    window.location.href = "login.php";

}


/* =========================================================
   CATEGORY FILTER
========================================================= */

const categoryButtons =
    document.querySelectorAll(".category-pill");

const productCards =
    document.querySelectorAll(".product-card");


categoryButtons.forEach(function(button) {

    button.addEventListener("click", function() {

        const selectedCategory =
            this.dataset.category;


        categoryButtons.forEach(function(btn) {

            btn.classList.remove("active");

        });


        this.classList.add("active");


        productCards.forEach(function(card) {

            const cardCategory =
                card.dataset.category;


            if (
                selectedCategory === "all" ||
                cardCategory === selectedCategory
            ) {

                card.style.display = "";

            } else {

                card.style.display = "none";

            }

        });

    });

});


/* =========================================================
   SEARCH
========================================================= */

const searchInput =
    document.getElementById("searchInput");


if (searchInput) {

    searchInput.addEventListener(
        "input",
        function() {

            const searchValue =
                this.value
                    .toLowerCase()
                    .trim();


            productCards.forEach(function(card) {

                const productName =
                    card.dataset.name;


                const category =
                    card.dataset.category
                        .toLowerCase();


                if (
                    productName.includes(searchValue) ||
                    category.includes(searchValue)
                ) {

                    card.style.display = "";

                } else {

                    card.style.display = "none";

                }

            });

        }
    );

}

</script>


</body>

</html>