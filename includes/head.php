<?php
$root = $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/';
$assets_path = 'http://' . $_SERVER['HTTP_HOST'] . '/UNICRIBS/assets/';
require_once $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/includes/head.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/core/classes/booking.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/core/classes/notification.php';

// Add this line to include the star rating function
require_once $_SERVER['DOCUMENT_ROOT'] . '/UNICRIBS/assets/components/star_rating.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo isset($page_title) ? $page_title : 'UNICRIBS'; ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
        <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#DC2626',
                        secondary: '#F87171'
                    },
                    borderRadius: {
                        'none': '0px',
                        'sm': '4px',
                        DEFAULT: '8px',
                        'md': '12px',
                        'lg': '16px',
                        'xl': '20px',
                        '2xl': '24px',
                        '3xl': '32px',
                        'full': '9999px',
                        'button': '8px'
                    }
                }
            }
        }
    </script>
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $assets_path; ?>images/cribs.ico" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo $assets_path; ?>images/cribs.ico" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:'#DC2626',secondary:'#F87171'},borderRadius:{'none':'0px','sm':'4px',DEFAULT:'8px','md':'12px','lg':'16px','xl':'20px','2xl':'24px','3xl':'32px','full':'9999px','button':'8px'}}}}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Remix Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.css">

    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/style.css">

    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/loader.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/scrollbar.css">

    
    <!-- Custom JS -->
    <script src="<?php echo $assets_path; ?>js/script.js" defer></script>
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCPMmHq7Cuad8V5zB1tF1ZHz7us-JKpnVo&libraries=places"></script>
        <!-- Google Translate Script -->
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <script>
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'en,fr',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE
            }, 'google_translate_element');
        }
    </script>
   

</head>