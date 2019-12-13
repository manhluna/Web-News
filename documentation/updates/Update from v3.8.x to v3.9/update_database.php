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
                        <h2 class="title">Update from v3.8.x to v3.9</h2>
                        <br><br>
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
