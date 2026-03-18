<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Database Connection
$host = '127.0.0.1';
$db   = 'hotelia_db';
$user = 'root';
$pass = ''; 
$charset = 'utf8mb4'; // Essential for reading the symbol from DB

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// 2. Fetch Categories
$categories = $pdo->query("SELECT * FROM menu_categories ORDER BY display_order")->fetchAll();

// 3. Start HTML Buffer
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 0.5in; }
        /* Using DejaVu Sans because it natively supports the Philippine Peso (₱) symbol */
        body { font-family: 'DejaVu Sans', sans-serif; color: #333; line-height: 1.4; font-size: 11pt; }
        
        .header { text-align: center; border-bottom: 2px solid #8b0000; margin-bottom: 30px; padding-bottom: 10px; }
        .header h1 { font-size: 28pt; margin: 0; color: #8b0000; text-transform: uppercase; letter-spacing: 2px; }
        .header p { font-style: italic; margin: 5px 0; font-size: 12pt; }
        
        .category-section { margin-bottom: 25px; }
        .category-name { 
            font-size: 16pt; 
            background: #fdf5e6; 
            padding: 5px 10px; 
            border-left: 5px solid #8b0000;
            margin-bottom: 15px;
            font-weight: bold;
        }
        
        .menu-item { margin-bottom: 12px; }
        .item-row { border-bottom: 1px dotted #ccc; height: 18px; margin-bottom: 2px; }
        .item-name { font-weight: bold; float: left; }
        .item-price { font-weight: bold; float: right; }
        .item-description { font-size: 9pt; color: #666; font-style: italic; margin-top: 4px; }
        
        .footer { text-align: center; font-size: 9pt; margin-top: 50px; border-top: 1px solid #ccc; padding-top: 10px; }
        .clear { clear: both; }
    </style>
</head>
<body>

<div class="header">
    <h1>Hotelia Traditional Kitchen</h1>
    <p>Authentic Filipino Flavors • Freshly Prepared Daily</p>
</div>

<?php foreach ($categories as $cat): ?>
    <div class="category-section">
        <div class="category-name"><?php echo htmlspecialchars($cat['category_name']); ?></div>
        
        <?php
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ? AND is_available = 1");
        $stmt->execute([$cat['category_id']]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item): ?>
            <div class="menu-item">
                <div class="item-row">
                    <span class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                    <span class="item-price">&#8369;<?php echo number_format($item['price'], 2); ?></span>
                    <div class="clear"></div>
                </div>
                <?php if(!empty($item['item_description'])): ?>
                    <div class="item-description"><?php echo htmlspecialchars($item['item_description']); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<div class="footer">
    <p>Prices are inclusive of VAT. Salamat sa pagbisita sa Hotelia!</p>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

// 4. Dompdf Rendering
$options = new Options();
// Set DejaVu Sans as default to ensure the Peso sign works everywhere
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 5. Output to Browser
$dompdf->stream("Hotelia_Menu.pdf", ["Attachment" => false]);
?>