$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Handle registration form submission
    $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        const username = $('#regUsername').val();
        const password = $('#regPassword').val();
        const confirmPassword = $('#regConfirmPassword').val();

        if (password !== confirmPassword) {
            alert('Passwords do not match!');
            return;
        }
        $.ajax({
            url: 'api/register.php',
            method: 'POST',
            data: JSON.stringify({
                username: username,
                password: password
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    alert(response.message || 'Registration failed');
                }
            },
            error: function() {
                alert('An error occurred during registration');
            }
        });
    });

    // Handle login form submission
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        const username = $('#loginUsername').val();
        const password = $('#loginPassword').val();

        $.ajax({
            url: 'api/login.php',
            method: 'POST',
            data: JSON.stringify({
                username: username,
                password: password
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    alert(response.message || 'Login failed');
                }
            },
            error: function() {
                alert('An error occurred during login');
            }
        });
    });
    // Handle passwprd reset
    $('#passwordresetForm').on('submit', function(e) {
        e.preventDefault();
        const username = $('#passwordresetUsername').val();
        $.ajax({
            url: 'api/reset_password.php',
            method: 'POST',
            data: JSON.stringify({
                username: username
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Password reset failed ');
                    window.location.href = 'index.php';
                } else {
                    alert(response.message || 'Password reset failed');
                }
            },
            error: function() {
                alert('An error occurred during Password reset');
            }
        });
    });
    // Handle demo video modal
    $('#demoModal').on('show.bs.modal', function() {
        // YouTube video ID will be added later
        const videoId = 'DG49kP1iyQk';
        $('#demoVideo').attr('src', `https://www.youtube.com/embed/${videoId}`);
    });

    $('#demoModal').on('hide.bs.modal', function() {
        $('#demoVideo').attr('src', '');
    });

    // Handle browse button click
    $('#browseBtn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#imageUpload').click();
    });

    // Handle drag and drop
    $('#dropZone').on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('border-primary');
    }).on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('border-primary');
    }).on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('border-primary');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });

    // Handle file selection
    $('#imageUpload').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            handleFileSelect(file);
        }
    });

    function handleFileSelect(file) {
        // Validate file type
        if (!file.type.match('image.*')) {
            alert('Please select an image file');
            return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            updateStatus('Image uploaded. Click submit to start..','status-uploaded');
            $('#previewImage').attr('src', e.target.result);
            $('#previewContainer').show();
            
        };
        reader.readAsDataURL(file);
    }

    // Handle image upload form submission
    $('#uploadForm').on('submit', function(e) {
        loader(true);
        e.preventDefault();
        const formData = new FormData(this);
        
        // Disable submit button
        $('#submitBtn').prop('disabled', true);
        
        // Show initial status
        updateStatus('uploaded image and processing..','status-uploaded');                
        idp = setInterval(pollStatusCheck,2000);
        $.ajax({
            url: 'api/upload.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                loader(false);
                if (response.success) {                    
                    startStatusPolling(response.transactionId);
                } else {
                    $('#submitBtn').prop('disabled', false);
                    updateStatus(response.message,'status-error');
                    clearInterval(idp);                    
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                loader(false);
                errorMessage = 'An error occurred during upload';
                try {
                    const response = JSON.parse(jqXHR.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {
                    errorMessage = errorThrown || textStatus || errorMessage;
                }
                //console.error('AJAX Error:', {
                //    status: jqXHR.status,
                 //   statusText: textStatus,
                //    errorThrown: errorThrown,
                 //   responseText: jqXHR.responseText
                //});
                alert(errorMessage);
                $('#submitBtn').prop('disabled', false);
                updateStatus(errorMessage,'status-error');
                clearInterval(idp);
            }
        });
    });

    // Status polling function
    function startStatusPolling(transactionId) {        
        const statusBadge = $('#statusBadge');
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

       // Initial status update
       // updateStatus('Uploaded...','status-uploaded')

        function pollStatus() {
            $.get('/api/status.php', { transaction_id: transactionId })
                .done(function(response) {
                    if (response.success) {
                        updateStatus(response.status,'status-uploaded')
                        switch(true) {
                            case /Generated/.test(response.status):
                                if (response.image_path) {  
                                    images = response.image_path; 
                                    image_names = response.image_names; 
                                    resultImage.attr('src', images[0]);
                                    thumbImage.forEach((thumb, index) => {
                                        if (images[index]) {
                                            thumb.attr('src', images[index]);
                                            thumb.attr('data-full-image', images[index]);                                            
                                            thumb.attr('title', image_names[index]);
                                        }                                                                                                                                            
                                    });
                                }
                            case /completed/.test(response.status):
                                document.getElementById('items').style.display = 'block';
                                updateStatus('Completed.','status-completed');                                    
                                if (response.image_path) {  
                                    images = response.image_path; 
                                    image_names = response.image_names; 
                                    resultImage.attr('src', images[0]);
                                    thumbImage.forEach((thumb, index) => {
                                        if (images[index]) {
                                            thumb.attr('src', images[index]);
                                            thumb.attr('data-full-image', images[index]);
                                            thumb.attr('title', image_names[index]);
                                        }                                                                                                                                            
                                    });

                                    resultContainer.removeClass('d-none');
                                    resultImage.removeClass('d-none');
                                    downloadBtn.removeClass('d-none')
                                        .attr('href', response.processed_image_path)
                                        .attr('download', 'generated_landscape.png');
                                    
                                    // Force a reflow to ensure visibility changes take effect
                                    resultContainer[0].offsetHeight;
                                    resultImage[0].offsetHeight;
                                    downloadBtn[0].offsetHeight;

                                } else {
                                    statusBadge.text('Completed (No Image Path)').removeClass().addClass('badge bg-warning');
                                }
                                $('#submitBtn').prop('disabled', false);
                                clearInterval(id);
                                clearInterval(idp);
                                
                                break;
                            case /error/.test(response.status):
                                statusBadge.text('Error').removeClass().addClass('badge bg-danger');
                                $('#submitBtn').prop('disabled', false);
                                clearInterval(id);    
                                clearInterval(idp);      
                                break;
                            default:
                                updateStatus(response.status,'status-processing')
                        }
                    } else {
                        statusBadge.text('Error Checking Status').removeClass().addClass('badge bg-danger');
                        $('#submitBtn').prop('disabled', false);
                        clearInterval(id);    
                        clearInterval(idp);
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    statusBadge.text('Error').removeClass().addClass('badge bg-danger');
                    $('#submitBtn').prop('disabled', false);
                    clearInterval(id);
                    clearInterval(idp);
                });
        }

        // Start the first poll
        id = setInterval(pollStatus,1000);
    }

    // Update status display
    function updateStatus(status,badgeType) {
        const statusBadge = $('.status-badge');
        statusBadge.removeClass().addClass('status-badge ' + badgeType);
        statusBadge.text(status.charAt(0).toUpperCase() + status.slice(1));
    }

    // Handle Stripe payment
    $('#buyCreditsBtn').on('click', function() {
        $.ajax({
            url: 'api/create-payment.php',
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    const stripe = Stripe(window.stripePublicKey);
                    stripe.redirectToCheckout({
                        sessionId: response.sessionId
                    });
                } else {
                    alert('Failed to initiate payment');
                }
            },
            error: function() {
                alert('Error processing payment request');
            }
        });
    });
}); 

document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('dropZone');
    const imageUpload = document.getElementById('imageUpload');
    const browseBtn = document.getElementById('browseBtn');

    // Trigger file input click when browse button is clicked
    browseBtn.addEventListener('click', function () {
        imageUpload.click();
    });

    // Prevent default behavior (open file in browser)
    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropZone.classList.add('dragover'); // Optional: Add visual feedback
    });

    dropZone.addEventListener('dragenter', function (e) {
        e.preventDefault();
        dropZone.classList.add('dragover'); // Optional: Add visual feedback
    });

    dropZone.addEventListener('dragleave', function () {
        dropZone.classList.remove('dragover'); // Optional: Remove visual feedback
    });

    // Handle the drop event
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropZone.classList.remove('dragover'); // Optional: Remove visual feedback

        const files = e.dataTransfer.files; // Get the dropped files
        if (files.length > 0) {
            // Assign the dropped file to the file input
            imageUpload.files = files;
            // Optional: Trigger a change event to handle file preview or validation
            const event = new Event('change');
            imageUpload.dispatchEvent(event);
        }
    });

});

 function loader(blnState) {
    const loader = document.getElementById('loader');
    if (blnState) {
        loader.style.display = 'block'; // Show the loader
    } else {
        loader.style.display = 'none'; // Hide the loader
    }
 }
loader(false)