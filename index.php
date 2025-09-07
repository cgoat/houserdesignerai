<?php
session_start();
require_once 'config.php';
require_once 'db.php';
require_once 'vendor/autoload.php';
//use GuzzleHttp\Client as GuzzleClient;
// Method 1: Create custom HTTP client
//$httpClient = new GuzzleClient([
//    'verify' => false,  // Disable SSL verification
//    'timeout' => 30,
//]);

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Initialize Google Client
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_CLIENT_URI);
//$client->setHttpClient($httpClient);
$client->addScope('email');
$client->addScope('profile');
$authUrl = $client->createAuthUrl();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>House Designer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style_v1.css?125" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-LNV0KYKKXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-LNV0KYKKXX');
</script>
</head>
<body>
    <div class="container" style="z-index: 10;">
        <div class="row justify-content-center mt-5" style="z-index: 10;">
            <div class="col-md-10" style="z-index: 10;">
                <div class="card" style="z-index: 10;">
                    <div class="card-body text-center" style="z-index: 10;">
                        <h1 class="mb-4">House Design</h1>
                        <p class="lead"><b><i>Upload a picture of your House Plan and start the design</i></b></p>
                        <button class="btn btn-tutorial mb-4">
                            <a href="testdrive.php" style="color: white;">Take a Test Drive</a>
                        </button>
                       
                        <div class="row" >
                            <div class="col-md-4">
                                <button class="btn btn-success w-100 mb-3" data-bs-toggle="modal" data-bs-target="#registerModal">
                                    Register
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#loginModal">
                                    Sign In
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#loginGModal">
                                    <a href="<?php echo $authUrl; ?>" style="color: white;">Login with Google</a>
                                </button>
                            </div>          
                                                 
                             <div class="email-container">
                                <span>✉️</span>
                                <a href="mailto:support@seagoat.org">support@seagoat.org</a><br>
                                <p><a href ="https://aistudio.google.com/prompts/new_chat?model=gemini-2.5-flash-image-preview">Powered by Nano Banana</a></>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Register</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="registerForm">
                        <div class="mb-3">
                            <label for="regUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="regUsername" required>
                        </div>
                        <div class="mb-3">
                            <label for="regPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="regPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="regConfirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="regConfirmPassword" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sign In</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="loginUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="loginUsername" required>
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="loginPassword" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Sign In</button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#passwordResetModal">
                                    Reset Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div class="modal fade" id="passwordResetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="passwordresetForm">
                        <div class="mb-3">
                            <label for="passwordresetUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="passwordresetUsername" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>    

    <!-- Demo Modal -->
    <div class="modal fade" id="demoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Demo Video</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="ratio ratio-16x9">
                       <iframe id="demoVideo" src="" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js?140"></script>
</body>
</html>
                                                  