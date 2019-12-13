<?php
define('BASEPATH', "/");
define('ENVIRONMENT', 'production');
require_once "application/config/database.php";
$license_code = '';
$purchase_code = '';

if (!function_exists('curl_init')) {
    $error = 'cURL is not available on your server! Please enable cURL to continue the installation. You can read the documentation for more information.';
}

function currentUrl($server)
{
    $http = 'http';
    if (isset($server['HTTPS'])) {
        $http = 'https';
    }
    $host = $server['HTTP_HOST'];
    $requestUri = $server['REQUEST_URI'];
    return $http . '://' . htmlentities($host) . '/' . htmlentities($requestUri);
}

function verify_license($input_code)
{
    $url = "http://license.codingest.com/api/check_infinite_license_code?license_code=" . $input_code . "&domain=" . currentUrl($_SERVER);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    if (empty($response)) {
        $url = "https://license.codingest.com/api/check_infinite_license_code?license_code=" . $input_code . "&domain=" . currentUrl($_SERVER);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
    }
    return json_decode($response);
}

//set database credentials
$database = $db['default'];
$db_host = $database['hostname'];
$db_name = $database['database'];
$db_user = $database['username'];
$db_password = $database['password'];

/* Connect */
$connection = mysqli_connect($db_host, $db_user, $db_password, $db_name);

if (!$connection) {
    $error = "Connect failed! Please check your database credentials.";
} else {

}

if (isset($_POST["btn_submit"])) {
    $input_code = trim($_POST['license_code']);
    $data = verify_license($input_code);
    if (!empty($data)) {
        if ($data->code == "error") {
            $error = "Invalid License Code!";
        } else {
            $license_code = $input_code;
            $purchase_code = $data->code;
            update();
            $success = 'Update completed successfully! Please delete "update_database.php" file.';
        }
    } else {
        $error = "Invalid License Code!";
    }
}

function update()
{
    global $purchase_code;
    global $license_code;
    global $connection;
    $connection->query("SET CHARACTER SET utf8");
    $connection->query("SET NAMES utf8");

    /*
     * =======================================================================================================
     * UPDATE TO VERSOIN 3.7
     * =======================================================================================================
     */

    $sql_general_settings = "CREATE TABLE `general_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY, 
        `site_lang` INT DEFAULT 1,
        `layout` VARCHAR(100) DEFAULT 'layout_1',
        `slider_active` INT DEFAULT 1,
        `site_color` VARCHAR(100) DEFAULT 'default',
        `show_pageviews` INT DEFAULT 1,
        `show_rss` INT DEFAULT 1,
        `logo_path` VARCHAR(255),
        `favicon_path` VARCHAR(255),
        `google_analytics` Text,
        `mail_protocol` VARCHAR(100) DEFAULT 'smtp',
        `mail_host` VARCHAR(255),
        `mail_port` VARCHAR(255) DEFAULT '587',
        `mail_username` VARCHAR(255),
        `mail_password` VARCHAR(255),
        `mail_title` VARCHAR(255),
        `primary_font` VARCHAR(255) DEFAULT 'open_sans',
        `secondary_font` VARCHAR(255) DEFAULT 'roboto',
        `tertiary_font` VARCHAR(255) DEFAULT 'verdana',
        `facebook_comment` Text,
        `pagination_per_page` INT DEFAULT 6,
        `menu_limit` INT DEFAULT 5,     
        `multilingual_system` INT DEFAULT 1,
        `registration_system` INT DEFAULT 1,
        `comment_system` INT DEFAULT 1,
        `emoji_reactions` INT DEFAULT 1,
        `head_code` Text,
        `inf_key` VARCHAR(500),
        `purchase_code` VARCHAR(500),
        `recaptcha_site_key` VARCHAR(255),
        `recaptcha_secret_key` VARCHAR(255),
        `recaptcha_lang` VARCHAR(50),
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;";


    $sql_settings = "CREATE TABLE `settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY, 
        `lang_id` INT DEFAULT 1,
        `application_name` VARCHAR(255),
        `site_title` VARCHAR(255),
        `home_title` VARCHAR(255),
        `site_description` VARCHAR(500),
        `keywords` VARCHAR(500),
        `facebook_url` VARCHAR(500),
        `twitter_url` VARCHAR(500),
        `google_url` VARCHAR(500),
        `instagram_url` VARCHAR(500),
        `pinterest_url` VARCHAR(500),
        `linkedin_url` VARCHAR(500),
        `vk_url` VARCHAR(500),
        `optional_url_button_name` VARCHAR(500) DEFAULT 'Click Here to Visit',
        `about_footer` VARCHAR(1000),
        `contact_text` Text,
        `contact_address` VARCHAR(500),
        `contact_email` VARCHAR(255),
        `contact_phone` VARCHAR(255),
        `copyright` VARCHAR(500),
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;";


    $sql_images = "CREATE TABLE `images` (
        `id` INT AUTO_INCREMENT PRIMARY KEY, 
        `image_big` VARCHAR(255),
        `image_mid` VARCHAR(255),
        `image_small` VARCHAR(255),
        `image_slider` VARCHAR(255)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $sql_languages = "CREATE TABLE `languages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY, 
        `name` VARCHAR(255),
        `short_form` VARCHAR(255),
        `language_code` VARCHAR(100),
        `folder_name` VARCHAR(255),
        `text_direction` VARCHAR(50),
        `status` INT DEFAULT 1,
        `language_order` INT DEFAULT 1
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $sql_polls = "CREATE TABLE `polls` (
        `id` INT AUTO_INCREMENT PRIMARY KEY, 
        `lang_id` INT DEFAULT 1,
        `question` Text,
        `option1` Text,
        `option2` Text,
        `option3` Text,
        `option4` Text,
        `option5` Text,
        `option6` Text,
        `option7` Text,
        `option8` Text,
        `option9` Text,
        `option10` Text,
        `status` INT DEFAULT 1,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;";


    $sql_poll_votes = "CREATE TABLE `poll_votes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY, 
        `poll_id` INT,
        `user_id` INT,
        `vote` VARCHAR(100)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $sql_reactions = "CREATE TABLE `reactions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY, 
        `post_id` INT,
        `re_like` INT DEFAULT 0,
        `re_dislike` INT DEFAULT 0,
        `re_love` INT DEFAULT 0,
        `re_funny` INT DEFAULT 0,
        `re_angry` INT DEFAULT 0,
        `re_sad` INT DEFAULT 0,
        `re_wow` INT DEFAULT 0
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;";


    /* update database */
    mysqli_query($connection, "DROP TABLE settings;");
    mysqli_query($connection, $sql_general_settings);
    mysqli_query($connection, $sql_settings);
    mysqli_query($connection, $sql_images);
    mysqli_query($connection, $sql_languages);
    mysqli_query($connection, $sql_polls);
    mysqli_query($connection, $sql_poll_votes);
    mysqli_query($connection, $sql_reactions);
    sleep(1);
    mysqli_query($connection, "ALTER TABLE categories ADD COLUMN `lang_id` INT DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE gallery_categories ADD COLUMN `lang_id` INT DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE pages ADD COLUMN `lang_id` INT DEFAULT 1;");
    mysqli_query($connection, "DELETE FROM pages WHERE slug='index';");
    mysqli_query($connection, "ALTER TABLE photos ADD COLUMN `lang_id` INT DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE posts ADD COLUMN `lang_id` INT DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE posts ADD COLUMN `status` INT DEFAULT 1;");
    mysqli_query($connection, "UPDATE pages SET slug='rss-feeds' WHERE slug='rss-channels';");
    mysqli_query($connection, "UPDATE pages SET slug='profile-update' WHERE slug='update-profile';");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `about_me` VARCHAR(5000);");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `facebook_url` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `twitter_url` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `google_url` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `instagram_url` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `pinterest_url` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `linkedin_url` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `vk_url` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `youtube_url` VARCHAR(500);");
    sleep(1);

    mysqli_query($connection, "INSERT INTO `general_settings` (`site_lang`, `layout`, `slider_active`, `site_color`, `show_pageviews`, `show_rss`, `logo_path`, `favicon_path`, `google_analytics`, `mail_protocol`, `mail_host`, `mail_port`, `mail_username`, `mail_password`, `mail_title`, `primary_font`, `secondary_font`, `tertiary_font`, `facebook_comment`, `pagination_per_page`, `menu_limit`, `multilingual_system`, `registration_system`, `comment_system`, `emoji_reactions`, `head_code`, `inf_key`, `purchase_code`, `recaptcha_site_key`, `recaptcha_secret_key`, `recaptcha_lang`) VALUES
        (1,'layout_1', 1, 'default', 1, 1, '', '','','smtp','','','','','Infinite', 'open_sans', 'roboto', 'verdana', '', 6, 6, 1,1,1,1, '','" . $license_code . "', '" . $purchase_code . "','','', 'en')");

    mysqli_query($connection, "INSERT INTO `settings` (`lang_id`, `application_name`, `site_title`, `home_title`, `site_description`, `keywords`, `facebook_url`, `twitter_url`, `google_url`, `instagram_url`, `pinterest_url`, `linkedin_url`, `vk_url`, `optional_url_button_name`, `about_footer`, `contact_text`, `contact_address`, `contact_email`, `contact_phone`, `copyright`) VALUES
        (1,'Infinite', 'Infinite - Blog Magazine Script', 'Index', 'Infinite - Blog Magazine Script', 'Infinite, Blog, Magazine', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Click Here To See More', NULL, NULL, NULL, NULL, NULL, 'Copyright © 2018 Infinite - All Rights Reserved.');");

    mysqli_query($connection, "INSERT INTO `languages` (`name`, `short_form`, `language_code`, `folder_name`, `text_direction`, `status`, `language_order`) VALUES ('English', 'en', 'en_us', 'default', 'ltr', 1, 1);");

    //add images to images table
    $sql = "SELECT * FROM posts ORDER BY id";
    $result = mysqli_query($connection, $sql);
    while ($row = mysqli_fetch_array($result)) {
        if (!empty($row['image_mid'])) {
            $insert = "INSERT INTO images (`image_big`, `image_mid`, `image_small`, `image_slider`) VALUES ('" . $row['image_big'] . "', '" . $row['image_mid'] . "', '" . $row['image_small'] . "', '" . $row['image_slider'] . "')";
            mysqli_query($connection, $insert);
        }
    }

    sleep(1);

    /*
     * =======================================================================================================
     * UPDATE TO VERSOIN 3.8
     * =======================================================================================================
     */
    $timestamp = date("Y-m-d H:i:s");
    $sql_followers = "CREATE TABLE `followers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY, 
        `following_id` int(11) DEFAULT NULL,
        `follower_id` int(11) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $sql_gallery_albums = "CREATE TABLE `gallery_albums` (
        `id` INT AUTO_INCREMENT PRIMARY KEY, 
        `lang_id` int(11) DEFAULT '1',
        `name` varchar(255) DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    /* update database */
    mysqli_query($connection, $sql_followers);
    mysqli_query($connection, $sql_gallery_albums);
    sleep(1);
    mysqli_query($connection, "ALTER TABLE comments ADD COLUMN `email` VARCHAR(255);");
    mysqli_query($connection, "ALTER TABLE comments ADD COLUMN `name` VARCHAR(255);");
    mysqli_query($connection, "ALTER TABLE gallery_categories ADD COLUMN `album_id` INT DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `mail_library` VARCHAR(100) DEFAULT 'swift';");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `send_email_contact_messages` INT DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `mail_options_account` VARCHAR(255);");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `cache_system` INT DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `cache_refresh_time` INT DEFAULT 1800;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `refresh_cache_database_changes` INT DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE general_settings DROP COLUMN `tertiary_font`;");
    mysqli_query($connection, "ALTER TABLE images ADD COLUMN `image_mime` VARCHAR(100) DEFAULT 'jpg';");
    mysqli_query($connection, "ALTER TABLE newsletters ADD COLUMN `token` VARCHAR(255);");
    sleep(1);
    mysqli_query($connection, "ALTER TABLE photos ADD COLUMN `album_id` INT DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE photos ADD COLUMN `is_album_cover` INT DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE posts ADD COLUMN `image_mime` VARCHAR(100) DEFAULT 'jpg';");
    mysqli_query($connection, "ALTER TABLE posts ADD COLUMN `post_type` VARCHAR(100) DEFAULT 'post';");
    mysqli_query($connection, "ALTER TABLE posts ADD COLUMN `video_url` VARCHAR(1000);");
    mysqli_query($connection, "ALTER TABLE posts ADD COLUMN `video_embed_code` VARCHAR(1000);");
    mysqli_query($connection, "ALTER TABLE posts ADD COLUMN `image_url` VARCHAR(1000);");
    mysqli_query($connection, "ALTER TABLE settings DROP COLUMN `google_url`;");
    mysqli_query($connection, "ALTER TABLE settings ADD COLUMN `cookies_warning` INT DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE settings ADD COLUMN `cookies_warning_text` text;");
    mysqli_query($connection, "ALTER TABLE users DROP COLUMN `google_url`;");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `token` VARCHAR(255);");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `last_seen` timestamp DEFAULT '2019-11-28 09:51:43'");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `show_email_on_profile` INT DEFAULT 1;");
    sleep(1);

    $sql = "SELECT * FROM languages";
    $result = mysqli_query($connection, $sql);
    while ($row = mysqli_fetch_array($result)) {
        if (!empty($row['id'])) {
            $insert = "INSERT INTO pages (`lang_id`, `title`, `slug`, `page_description`, `page_keywords`, `is_custom`, `page_content`, `page_order`, `page_active`, `title_active`, `breadcrumb_active`, `right_column_active`, `need_auth`, `location`, `parent_id`) 
                VALUES ('" . $row['id'] . "', 'Terms & Conditions', 'terms-conditions', 'Terms & Conditions Page','infinite, terms, conditions, page',0,NULL,0,1,1,1,0,0,'footer',0)";
            mysqli_query($connection, $insert);

            $insert = "INSERT INTO  gallery_albums (`lang_id`, `name`) 
                VALUES ('" . $row['id'] . "', 'Album 1')";
            mysqli_query($connection, $insert);
        }
    }

    $sql = "UPDATE users SET last_seen='" . $timestamp . "'";
    mysqli_query($connection, $sql);

    sleep(1);

    /*
     * =======================================================================================================
     * UPDATE TO VERSOIN 3.9
     * =======================================================================================================
     */

    $table_files = "CREATE TABLE `files` (
		 `id` INT AUTO_INCREMENT PRIMARY KEY,
		  `file_name` varchar(255) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $table_post_files = "CREATE TABLE `post_files` (
	 `id` INT AUTO_INCREMENT PRIMARY KEY,
	  	`post_id` int(11) DEFAULT NULL,
	  	`file_id` int(11) DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    mysqli_query($connection, $table_files);
    mysqli_query($connection, $table_post_files);
    sleep(1);
    mysqli_query($connection, "ALTER TABLE comments ADD COLUMN `status` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `dark_mode` TINYINT(1) DEFAULT 0;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `timezone` VARCHAR(255) DEFAULT 'America/New_York';");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `facebook_app_id` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `facebook_app_secret` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `google_client_id` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `google_client_secret` VARCHAR(500);");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `google_adsense_code` TEXT;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `comment_approval_system` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `approve_posts_before_publishing` TINYINT(1) DEFAULT 1;");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `emoji_reactions_type` VARCHAR(10) DEFAULT 'gif';");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `text_editor_lang` VARCHAR(10) DEFAULT 'en';");
    mysqli_query($connection, "ALTER TABLE general_settings ADD COLUMN `mobile_logo_path` VARCHAR(255)");
    mysqli_query($connection, "ALTER TABLE general_settings DROP COLUMN `created_at`;");

    mysqli_query($connection, "ALTER TABLE images ADD COLUMN `file_name` VARCHAR(255);");
    mysqli_query($connection, "RENAME TABLE newsletters TO subscribers;");
    mysqli_query($connection, "ALTER TABLE post_images DROP COLUMN `created_at`;");
    mysqli_query($connection, "ALTER TABLE reading_lists DROP COLUMN `created_at`;");
    mysqli_query($connection, "ALTER TABLE settings DROP COLUMN `created_at`;");
    mysqli_query($connection, "ALTER TABLE tags DROP COLUMN `created_at`;");

    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `user_type` VARCHAR(30) DEFAULT 'registered';");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `google_id` VARCHAR(255);");
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN `facebook_id` VARCHAR(255);");

    //update categories
    $sql = "SELECT * FROM posts ORDER BY id";
    $result = mysqli_query($connection, $sql);
    while ($row = mysqli_fetch_array($result)) {
        $cat_id = 0;
        if (!empty($row['subcategory_id'])) {
            $cat_id = $row['subcategory_id'];
        } elseif (!empty($row['category_id'])) {
            $cat_id = $row['category_id'];
        }
        mysqli_query($connection, "UPDATE posts SET `category_id`=" . $cat_id . " WHERE id=" . $row['id']);
    }
    sleep(1);

    mysqli_query($connection, "ALTER TABLE posts DROP COLUMN `subcategory_id`;");
    mysqli_query($connection, "UPDATE general_settings SET inf_key='" . $license_code . "', purchase_code='" . $purchase_code . "' WHERE id='1'");
    sleep(2);
    /* close connection */
    mysqli_close($connection);
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Infinite - Update Wizard</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,700" rel="stylesheet">
    <!-- Font-awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #444 !important;
            font-size: 14px;

            background: #007991; /* fallback for old browsers */
            background: -webkit-linear-gradient(to left, #007991, #6fe7c2); /* Chrome 10-25, Safari 5.1-6 */
            background: linear-gradient(to left, #007991, #6fe7c2); /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */

        }

        .logo-cnt {
            text-align: center;
            color: #fff;
            padding: 60px 0 60px 0;
        }

        .logo-cnt .logo {
            font-size: 42px;
            line-height: 42px;
        }

        .logo-cnt p {
            font-size: 22px;
        }

        .install-box {
            width: 100%;
            padding: 30px;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            margin: auto;
            background-color: #fff;
            border-radius: 4px;
            display: block;
            float: left;
            margin-bottom: 100px;
        }

        .form-input {
            box-shadow: none !important;
            border: 1px solid #ddd;
            height: 44px;
            line-height: 44px;
            padding: 0 20px;
        }

        .form-input:focus {
            border-color: #239CA1 !important;
        }

        .btn-custom {
            background-color: #239CA1 !important;
            border-color: #239CA1 !important;
            border: 0 none;
            border-radius: 4px;
            box-shadow: none;
            color: #fff !important;
            font-size: 16px;
            font-weight: 300;
            height: 40px;
            line-height: 40px;
            margin: 0;
            min-width: 105px;
            padding: 0 20px;
            text-shadow: none;
            vertical-align: middle;
        }

        .btn-custom:hover, .btn-custom:active, .btn-custom:focus {
            background-color: #239CA1;
            border-color: #239CA1;
            opacity: .8;
        }

        .tab-content {
            width: 100%;
            float: left;
            display: block;
        }

        .tab-footer {
            width: 100%;
            float: left;
            display: block;
        }

        .buttons {
            display: block;
            float: left;
            width: 100%;
            margin-top: 30px;
        }

        .title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            margin-top: 0;
            text-align: center;
        }

        .sub-title {
            font-size: 14px;
            font-weight: 400;
            margin-bottom: 30px;
            margin-top: 0;
            text-align: center;
        }

        .alert {
            text-align: center;
        }

        .alert strong {
            font-weight: 500 !important;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row">
        <div class="col-md-8 col-sm-12 col-md-offset-2">

            <div class="row">
                <div class="col-sm-12 logo-cnt">
                    <h1>Infinite</h1>
                    <p>Welcome to the Update Wizard</p>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="install-box">
                        <h2 class="title">Update from v3.6.x to v3.9</h2>
                        <br><br>
                        <p class="text-center">
                            <a href="http://license.codingest.com/infinite-license" target="_blank" class="btn btn-success">Generate License Code</a>
                        </p>
                        <div class="messages">
                            <?php if (!empty($error)) { ?>
                                <div class="alert alert-danger">
                                    <strong><?php echo $error; ?></strong>
                                </div>
                            <?php } ?>
                            <?php if (!empty($success)) { ?>
                                <div class="alert alert-success">
                                    <strong><?php echo $success; ?></strong>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="step-contents">
                            <div class="tab-1">
                                <?php if (empty($success)): ?>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                        <div class="tab-content">
                                            <div class="tab_1">
                                                <div class="form-group">

                                                    <label for="email">License Code</label>
                                                    <textarea name="license_code" class="form-control form-input"
                                                              style="resize: vertical; min-height: 80px; height: 80px; line-height: 24px;padding: 10px;"
                                                              placeholder="Enter License Code"
                                                              required><?php echo $license_code; ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-footer">
                                            <button type="submit" name="btn_submit" class="btn-custom pull-right">Update
                                                My
                                                Database
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
