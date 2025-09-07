<?php
session_start();
require_once 'config.php';
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
function getEmailDomain($email) {
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }
    
    return substr(strrchr($email, "@"), 1);
}
$isButtonDisabled = (getEmailDomain($_SESSION['username']) === "testdrive.com");
//$_SESSION['status'] = "Upload a image of your lawn to get started.."; 
$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - House Designer</title>
    <script>
        window.stripePublicKey = '<?php echo STRIPE_PUBLIC_KEY; ?>';
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style_dashboard.css?12122" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">House Designer</a>
            <div class="d-flex align-items-center">
               
                <div class="credits-display me-3" style="align-items: bottom;">
                    Credits: $<?php echo number_format($user['credits'], 2); ?>
                </div>
                <button id="buyCreditsBtn" <?php echo $isButtonDisabled ? 'disabled' : ''; ?>  class="btn btn-success me-3" data-bs-toggle="tooltip" title="Buy more credits">
                    Buy Credits
                </button>
                <a href="api/logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Left Column - 30% -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Upload a House Plan</h2>
                        
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="upload-area mb-4" id="dropZone">
                                <input type="file" id="imageUpload" name="image" accept="image/*" class="d-none">
                                <button type="button" id="browseBtn" class="btn btn-outline-primary mb-3">Browse Files</button>
                                <p class="mb-0">Drag and drop your image here or Drag and drop one of the sample images below</p>
                            </div>
                                <div class="upload-thumbnails mb-3 text-center">                                    
                                <img src="./images/sample/1.png" class="upload-thumbnail" width= 20% height =20% alt="Sample House Plan 1" data-image-path="./images/sample/1.png">
                                <img src="./images/sample/2.jpg?" class="upload-thumbnail" width= 20% height =20% alt="Sample House Plan 2" data-image-path="./images/sample/2.jpg">
                                <img src="./images/sample/3.jpg?" class="upload-thumbnail" width= 20% height =20% alt="Sample House Plan 3" data-image-path="./images/sample/3.jpg">
                         </div>

                            <div class="mb-3">
                                <label for="prompt" class="form-label">Prompt:</label>
                                <textarea class="form-control" id="prompt" name="prompt" rows="3" 
                                     required>Based on the attached plan, create a ranch house. Show the picture of the front elevation of the home. Also show the 45 degree orthogonal view of the house without the roof. Pay special attention to the windows, door, partitions in the plan. Create an image for every room </textarea>
                            </div>

                            <div class="text-center">
                                <div >                                    
                                        <span class="status-badge status-uploaded text-center">Upload a image of your house plan to get started..</span>
                                </div>  
                                <br>               
                                <div class="loader" id="loader"></div>                                             
                                <button type="submit" class="btn btn-primary" id="submitBtn" 
                                    data-bs-toggle="tooltip" title="Submit your image for processing"
                                    <?php echo $user['credits'] < TRANSACTION_COST ? 'disabled' : ''; ?>>
                                    Submit
                                </button>                              
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column - 70% -->
<div class="col-md-8">
    <div class="row">
        <!-- Preview Section - Top Row -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="parentContainer" style="display: flex;">
                        <!-- Main Image Containers -->
                        <div style="flex: 1;">
                            <div id="previewContainer">
                                <img id="previewImage" class="img-fluid" src="./images/others/blank.jpg" alt="Preview">
                            </div>
                        </div>
                         <div style="flex: 1;">                            
                            <div id="resultContainer" style="position: relative; display: inline-block;">
                                <div style="position: absolute; top: 325px; left: 150px; z-index: 10;">
                                    <a id="downloadBtn" href="./images/others/blank.jpg" class="btn btn-primary d-none"  data-bs-toggle="tooltip" title="Download image" download>
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>                                
                                <img id="resultImage" class="img-fluid" src="./images/others/blank.jpg" >
                                <p id="img_desc"> </p>
                            </div>
                        </div>
                        <!-- Thumbnail Container -->
                        <div id="thumbnailContainer" style="width: 50px; height: 50px; margin-top: 24px; margin-left: 10px;">
                            <div class="thumbnail" style="margin-bottom: 10px;">
                                <img id = "thumbImage1" src="./images/others/blank.jpg?" class="img-fluid-1 thumbnail-image"  style="width: 100%; height: 100%;  cursor: pointer;" data-full-image="./images/others/blank.jpg" title="">
                            </div>
                            <div class="thumbnail" style="margin-bottom: 10px;">
                                <img id = "thumbImage2"  src="./images/others/blank.jpg?" class="img-fluid-1 thumbnail-image"  style="width: 100%; height: 100%;  cursor: pointer;" data-full-image="./images/others/blank.jpg" title="">
                            </div>
                            <div  class="thumbnail" style="margin-bottom: 10px;">
                                <img id = "thumbImage3" src="./images/others/blank.jpg?" class="img-fluid-1 thumbnail-image"  style="width: 100%; height: 100%; cursor: pointer;" data-full-image="./images/others/blank.jpg" title="">
                            </div>
                            <div class="thumbnail" style="margin-bottom: 10px;">
                                <img id = "thumbImage4"  src="./images/others/blank.jpg?" class="img-fluid-1 thumbnail-image"  style="width: 100%; height: 100%; cursor: pointer;" data-full-image="./images/others/blank.jpg" title="">
                            </div>
                            <div  class="thumbnail" style="margin-bottom: 10px;">
                                <img id = "thumbImage5" src="./images/others/blank.jpg?" class="img-fluid-1 thumbnail-image"  style="width: 100%; height: 100%; cursor: pointer;" data-full-image="./images/others/blank.jpg" title="">
                            </div>
                        </div>
                        <div id="thumbnailContainer" style="width: 50px; height: 50px; margin-top: 24px; margin-left: 10px;">
                            <div class="thumbnail" style="margin-bottom: 10px;">
                                <img id = "thumbImage6" src="./images/others/blank.jpg?" class="img-fluid-1 thumbnail-image"  style="width: 100%; height: 100%;  cursor: pointer;" data-full-image="./images/others/blank.jpg" title="">
                            </div>
                            <div class="thumbnail" style="margin-bottom: 10px;">
                                <img id = "thumbImage7"  src="./images/others/blank.jpg?" class="img-fluid-1 thumbnail-image"  style="width: 100%; height: 100%;  cursor: pointer;" data-full-image="./images/others/blank.jpg" title="">
                            </div>
                            <div  class="thumbnail" style="margin-bottom: 10px;">
                                <img id = "thumbImage8" src="./images/others/blank.jpg?" class="img-fluid-1 thumbnail-image"  style="width: 100%; height: 100%; cursor: pointer;" data-full-image="./images/others/blank.jpg" title="">
                            </div>
                            <div class="thumbnail" style="margin-bottom: 10px;">
                                <img id = "thumbImage9"  src="./images/others/blank.jpg?" class="img-fluid-1 thumbnail-image"  style="width: 100%; height: 100%; cursor: pointer;" data-full-image="./images/others/blank.jpg" title="">
                            </div>
                            <div  class="thumbnail" style="margin-bottom: 10px;">
                                <img id = "thumbImage10" src="./images/others/blank.jpg?" class="img-fluid-1 thumbnail-image"  style="width: 100%; height: 100%; cursor: pointer;" data-full-image="./images/others/blank.jpg" title="">
                            </div>
                        </div>                                        
                    </div>
                           <div  id="items" style="margin-bottom: 10px; display: none">
                                <p>To add items to the image, select the item and click generate</p>
                                <img id = "item" src="./images/items/sofa_500.jpg?" class="item-image"  style="width: 10%; height: 10%;"  data-full-image="./images/items/sofa_500.jpg?" title="Sofa at Ashley furniture. MRP $400">
                                <img id = "item" src="./images/items/sofa_700.jpg?" class="item-image"  style="width: 10%; height: 10%;"  data-full-image="./images/items/sofa_700.jpg?" title="Sofa at Amazon furniture. MRP $700">
                                <img id = "item" src="./images/items/sofa_800.jpg?" class="item-image"  style="width: 10%; height: 10%;"  data-full-image="./images/items/sofa_800.jpg?" title="Sofa at Amazon furniture. MRP $800">
                                <img id = "item" src="./images/items/dinning_400.jpg?" class="item-image"  style="width: 10%; height: 10%;"  data-full-image="./images/items/sofa_700.jpg?" title="Dinning table at Amazon furniture. MRP $400">
                                <img id = "item" src="./images/items/dinning_600.jpg?" class="item-image"  style="width: 10%; height: 10%;"  data-full-image="./images/items/sofa_700.jpg?" title="Dinning table at Amazon furniture. MRP $600">
                            </div>                              

                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Thumbnail Hover -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const thumbnails = document.querySelectorAll('.thumbnail-image');
        const resultImage = document.getElementById('resultImage');
        const downloadBtn = document.getElementById('downloadBtn');
        const imgDesc = document.getElementById('img_desc');

        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('mouseenter', function () {
                const fullImageSrc = this.getAttribute('data-full-image');
                resultImage.src = fullImageSrc;
                resultImage.alt = this.alt;
                imgDesc.textContent  = this.getAttribute('title');
                downloadBtn.href = fullImageSrc; // Update download button href
            });
        });

        // Initialize tooltips (if using Bootstrap)
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    });
</script>
    <script src="js/main.js?t=422"></script>
    <script>
        function updateStatus(status,badgeType) {
            const statusBadge = $('.status-badge');
            statusBadge.removeClass().addClass('status-badge ' + badgeType);
            statusBadge.text(status.charAt(0).toUpperCase() + status.slice(1));
        }
        function pollStatusCheck() {
            const resultImage = $('#resultImage');
            const thumbImage1 = $('#thumbImage1');
            const thumbImage2 = $('#thumbImage2');
            const thumbImage3 = $('#thumbImage3');
            const thumbImage4 = $('#thumbImage4');
            const thumbImage5 = $('#thumbImage5');
            const thumbImage6 = $('#thumbImage6');
            const thumbImage7 = $('#thumbImage7');
            const thumbImage8 = $('#thumbImage8');
            const thumbImage9 = $('#thumbImage9');
            const thumbImage10 = $('#thumbImage10');            
            const downloadBtn = $('#downloadBtn');
            const thumbImage = [thumbImage1,thumbImage2,thumbImage3,thumbImage4,thumbImage5,thumbImage6,thumbImage7,thumbImage8,thumbImage9,thumbImage10] 
            const resultContainer = $('#resultContainer'); 
            $.get('/api/status.php?status_check=get&user_id=<?php echo $_SESSION['user_id']?>')
                .done(function(response) {
                    if (response.success) {
                       updateStatus(response.status,'status-processing')
                                if ((response.image_path) && (response.status.includes("Generated"))) {  
                                    images = response.image_path; 
                                    resultImage.attr('src', images[0]);
                                    thumbImage.forEach((thumb, index) => {
                                        if (images[index]) {
                                            thumb.attr('src', images[index]);
                                            thumb.attr('data-full-image', images[index]);
                                        }                                                                                                                                            
                                    });
                                }                       
                    }
               })
        }
    </script>
</body>
</html> 