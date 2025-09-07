<?php
require_once '../config.php';
require_once '../db.php';

header('Content-Type: application/json');
$adultWords = [ 'adult', 'porn', 'sex', 'xxx', 'nude', 'naked', 'explicit',
  'inappropriate', 'offensive', 'obscene', 'vulgar', 'profanity',
  'curse', 'swear', 'drug', 'illegal', 'weapon', 'violence',
  'hate', 'discrimination', 'racist', 'sexist', 'abuse']; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'status'=> 'error', 'message' => 'Method not allowed']);
    exit();
}

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'status'=> 'error', 'message' => 'Unauthorized']);
    exit();
}

try {
    $pdo = getDbConnection();
    
    // Check user credits
    $stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['status'] = "Checking Credits required for processing.."; 
    if ($user['credits'] < TRANSACTION_COST) {
        echo json_encode(['success' => false, 'status'=> 'error', 'message' => 'Insufficient credits']);
        $_SESSION['status'] = "Insufficient credits"; 
        exit();
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'status'=> 'error', 'message' => 'No image uploaded']);
        $_SESSION['status'] = "No Image uploaded"; 
        exit();
    }

    $prompt = $_POST['prompt'] ?? '';
    $promptLower = strtolower($prompt);

    // Check for adult words
    $foundAdultWord = false;
    foreach ($adultWords as $word) {
        if (strpos($promptLower, strtolower($word)) !== false) {
            $foundAdultWord = true;
            break;
        }
    }

    if ($foundAdultWord) {
        echo json_encode(['success' => false, 'status'=> 'error', 'message' => 'Inappropriate language  found in the prompt. Please fix']);
        $_SESSION['status'] = "Remove Inappropriate language found in the prompt "; 
        exit();
    }

    $prompt = $prompt . ' Dont change the house.';
    if (empty($prompt)) {
        echo json_encode(['success' => false, 'status'=> 'error' , 'message' => 'Prompt is required']);
        $_SESSION['status'] = "Enter Prompt"; 
        exit();
    }

    // Generate unique filename
    $filename = uniqid() . '_' . basename($_FILES['image']['name']);
    $uploadPath = UPLOAD_DIR . $filename;

    // Move uploaded file
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to move uploaded file');
    }
    $_SESSION['status'] = "Uploaded the image.."; 
    // Create transaction record
    $stmt = $pdo->prepare("
        INSERT INTO transactions (user_id, original_image_path, prompt, status)
        VALUES (?, ?, ?, 'uploaded')
    ");
    $stmt->execute([$_SESSION['user_id'], $uploadPath, $prompt]);
    $transactionId = $pdo->lastInsertId();
    
    $_SESSION['status'] = "Generating AI images...";
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET status = ?, process_time = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute(["Generating AI images...", $transactionId]);    
        
         if (DEBUG_MODE) {
            logError("Generating AI images...");
         }           
    // Call image generation API
    //$genResponse = callImageGenerationApi($uploadPath, $maskedPath, $prompt);
    $genResponse = callImageGenerationAPIGoogle($filename,$uploadPath,  $prompt, $pdo, $transactionId);
    //$genResponse = callImageGenerationAPIOpenAI($uploadPath, $maskedPath, $prompt);

    if (!$genResponse['success']) {
        throw new Exception('Image generation API failed ' );             
        //throw new Exception('Image generation API failed ' );             
    }else{
    
        // Create a response array
        $response = [
            'files' => $genResponse['processedFiles'],
            'filesName' => $genResponse['processedFilesName']

        ];    
        $jsonOutput = json_encode($response);
        // Update transaction with processed image
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET processed_image_path = ?, status = 'completed', process_time = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$jsonOutput, $transactionId]);
        $_SESSION['status'] = "completed"; 
        // Deduct credits
        $stmt = $pdo->prepare("
            UPDATE users 
            SET credits = credits - ? 
            WHERE id = ?
        ");
        $stmt->execute([TRANSACTION_COST, $_SESSION['user_id']]);
            echo json_encode([
                'success' => true,
                'status'=> 'completed',
                'message'=> 'completed',
                'transactionId' => $transactionId,
                'imagePath' => $genResponse['processedFiles']
            ]);        
    }
} catch (Exception $e) {
    // Update transaction with error
    if (isset($transactionId)) {
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET status = 'error', error_message = ?
            WHERE id = ?
        ");
        $stmt->execute([$e->getMessage(), $transactionId]);
    }
    $_SESSION['status'] = 'error'; 
  
    echo json_encode(['success' => false, 'status'=> 'error', 'message' => "Error Processing"]);
}


/**
 * Call Google Generative AI API to generate images with an input image and prompt
 *
 * @param string $imagePath Path to the input image file
 * @param string $prompt The text prompt for image generation
 * @param int $numImages Number of images to generate
 * @return array Response with success status, images, and error details
 */
function callImageGenerationAPIGoogle($filename, $imagePath, $prompt,$pdo,$transactionId) {
    $results = [
        'success' => true,
        //'images' => [],
        'messages' => [],
        'http_codes' => [],
        'raw_responses' => [],
        'processedFiles' => [],
        'processedFilesName' => []
    ];
    try{
        // Validate inputs
        if (!file_exists($imagePath) || !is_readable($imagePath)) {
            return [
                'success' => false,
                'message' => 'Image file does not exist or is not readable'
            ];
        }
        if (empty($prompt)) {
            return [
                'success' => false,
                'message' => 'Prompt cannot be empty'
            ];
        }
        $numImages = 1;

        // Read and encode the image to base64
        $imageData = file_get_contents($imagePath);
        if ($imageData === false) {
            return [
                'success' => false,
                'message' => 'Failed to read image file'
            ];
        }
        $imageBase64 = base64_encode($imageData);
        if ($imageBase64 === false) {
            return [
                'success' => false,
                'message' => 'Failed to encode image to base64'
            ];
        }

        // Determine MIME type
        $mimeType = mime_content_type($imagePath);
        if ($mimeType === false) {
            $mimeType = 'image/jpeg'; // Fallback to JPEG, as per cURL command
        }
       
        // Loop to generate multiple images
        #for ($i = 0; $i < $numImages; $i++) {
                // Prepare the request payload
                $payload = [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $imageBase64
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                // Convert payload to JSON
                $jsonPayload = json_encode($payload);
                if (DEBUG_MODE) {
                    logError("Step 1");
                }                    
                if ($jsonPayload === false) {
                    $results['success'] = false;
                    $results['messages'][] = "Failed to encode JSON payload for image";
                    #continue;
                }

                // Initialize cURL
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
                curl_setopt($ch, CURLOPT_TIMEOUT, 300); 
                curl_setopt($ch, CURLOPT_URL, GOOGLE_API_ENDPOINT . '?key=' . GOOGLE_API_KEY);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonPayload)
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Enable SSL verification (set to false for testing only)

                // Execute the request
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                #if (DEBUG_MODE) {
                    #logError($response);
                #} 

                // Check for cURL errors
                if ($response === false) {
                    $results['success'] = false;
                    $results['messages'][] = "cURL error for image : " . $error;
                    #continue;
                }

                // Check for HTTP errors
                if ($httpCode !== 200) {
                    $results['success'] = false;
                    $results['messages'][] = "HTTP error for image : Code $httpCode";
                    $results['http_codes'][] = $httpCode;
                    $results['raw_responses'][] = $response;
                    #continue;
                }

                // Parse the response
                $decodedResponse = json_decode($response, true);
       
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $results['success'] = false;
                    $results['messages'][] = "Invalid JSON response for image : " . json_last_error_msg();
                    $results['http_codes'][] = $httpCode;
                    $results['raw_responses'][] = $response;
                    #continue;
                }

                // Ensure the response has the required fields
                if (!isset($decodedResponse['candidates'][0]['content']['parts'])) {
                    $results['success'] = false;
                    $results['messages'][] = "API response missing required parts for image " ;
                    $results['http_codes'][] = $httpCode;
                    $results['raw_responses'][] = $response;
                    #continue;
                }

                // Process the response parts
                $imageFound = false;
                $i=0;
                foreach ($decodedResponse['candidates'][0]['content']['parts'] as $part) {                  
                    if (isset($part['text'])) {
                        $results['messages'][] = "Text for image : " . $part['text'];
                        $results['processedFilesName'][] = $part['text'];     
                        $imageFilesName= [
                            'names' => $results['processedFilesName']
                        ];                              
                    } elseif (isset($part['inlineData']['data'])) {
                        $i=$i+1;
                        // Decode the base64 image data
                        $generatedImageData = base64_decode($part['inlineData']['data']);
                        if (DEBUG_MODE) {
                            logError("Parsing Image");
                            logError($generatedImageData);
                        }               
                        if ($generatedImageData === false) {
                            $results['success'] = false;
                            $results['messages'][] = "Failed to decode image data for image ";
                            continue;
                        }

                        // Store the base64-encoded image data in the results
                        //$results['images'][] = ['data:image/png;base64,' . $part['inlineData']['data']];
                        // Save generated image
                        $processedPath = PROCESSED_DIR .$i."_". $filename;                                                   
                        file_put_contents($processedPath, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', 'data:image/png;base64,' . $part['inlineData']['data'])));
                        $results['processedFiles'][] = $processedPath;

                        $imageFiles= [
                            'files' => $results['processedFiles'],
                            'filesName' => $results['processedFilesName']
                        ];    
                        #$jsonOutput = json_encode($imageFiles);
                        $jsonOutput = json_encode($imageFiles);

                        $stmt = $pdo->prepare("
                                UPDATE transactions 
                                SET status = ?, processed_image_path = ?,  process_time = CURRENT_TIMESTAMP
                                WHERE id = ?
                            ");
                        $stmt->execute(["Generated AI image no: ". (string) $i+1,$jsonOutput, $transactionId]);                        

                        $results['messages'][] = "Saved image";
                        $imageFound = true;

                        // Free the image resource
                        //imagedestroy($image);
                    }                   
                }
                    
                if (!$imageFound) {
                    $results['success'] = false;
                    $results['messages'][] = "No image data found in response for image " ;
                    $results['http_codes'][] = $httpCode;
                    $results['raw_responses'][] = $response;
                }

                // Add a delay to avoid rate limits
         #       sleep(1);        
        #}
    return $results;
    }catch (Exception $e) {
         if (DEBUG_MODE) {
            logError($e->getMessage());
         }        
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];    
    } 
}
?> 