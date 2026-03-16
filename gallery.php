<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get gallery images (you can create a gallery table or use product images)
$query = "SELECT image, name, description FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT 12";
$stmt = $db->prepare($query);
$stmt->execute();
$gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - Mercy Pastries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="css/style.css">
    <style>
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            cursor: pointer;
            margin-bottom: 30px;
        }
        
        .gallery-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .gallery-item:hover img {
            transform: scale(1.1);
        }
        
        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }
        
        .gallery-overlay h5 {
            color: white;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <section class="py-5">
        <div class="container">
            <h1 class="section-title text-center mb-5">Our Gallery</h1>
            <div class="row g-4">
                <?php foreach ($gallery_images as $index => $image): ?>
                <div class="col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
                    <div class="gallery-item" onclick="openLightbox('<?php echo $image['image']; ?>', '<?php echo $image['name']; ?>')">
                        <img src="<?php echo $image['image']; ?>" alt="<?php echo $image['name']; ?>">
                        <div class="gallery-overlay">
                            <h5><?php echo $image['name']; ?></h5>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Lightbox Modal -->
    <div class="modal fade" id="lightboxModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center">
                    <img src="" id="lightboxImage" class="img-fluid rounded-3" style="max-height: 80vh;">
                    <h5 id="lightboxCaption" class="text-white mt-3"></h5>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function openLightbox(src, caption) {
            document.getElementById('lightboxImage').src = src;
            document.getElementById('lightboxCaption').textContent = caption;
            new bootstrap.Modal(document.getElementById('lightboxModal')).show();
        }
    </script>
</body>
</html>