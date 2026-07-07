<?php
/**
 * MAZADI - Complete Car Auction Platform
 * Master Plan for Complete Rebuild
 * 
 * Database: Uses existing database.sql schema (correct)
 * Tables: users, seller_companies, vehicles, inspections, 
 *         auction_events, auctions, bids, auto_bids, 
 *         transactions, watchlist, notifications
 */

// Files to rebuild completely:
$files = [
    'config.php'                  => 'Central config + DB + helpers',
    'includes/navbar.php'         => 'Unified navbar',
    'includes/footer.php'         => 'Footer',
    'index.php'                   => 'Home page - live data',
    'auctions.php'                => 'Browse auctions - live data + filters',
    'vehicle-details.php'         => 'Vehicle details + bid panel',
    'login.php'                   => 'Login page',
    'register.php'                => 'Register page',
    'logout.php'                  => 'Logout',
    'profile.php'                 => 'User profile',
    'buyer/dashboard.php'         => 'Buyer dashboard',
    'seller/dashboard.php'        => 'Seller dashboard',
    'seller/add-vehicle.php'      => 'Add vehicle form',
    'seller/my-auctions.php'      => 'Seller auctions list',
    'admin/index.php'             => 'Admin dashboard',
    'admin/users.php'             => 'Admin - manage users',
    'admin/auctions.php'          => 'Admin - manage auctions',
    'admin/approvals.php'         => 'Admin - approve vehicles',
    'api/bid.php'                 => 'AJAX bid endpoint',
    'api/watchlist.php'           => 'AJAX watchlist toggle',
    'api/notifications.php'       => 'Get notifications',
    'setup_users.php'             => 'One-time setup script',
];
?>
