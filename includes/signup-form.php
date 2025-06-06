

<?php
if ($_SERVER['REQUEST_METHOD'] == "GET" && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	header('Location: ../index.php');
}
if (isset($_POST['signup'])) {
    $screenName = $_POST['screenName'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $usertype = $_POST['usertype'];
    $error = '';

    if (empty($screenName) || empty($password) || empty($email) || empty($usertype)) {
        $error = 'All fields are required';
    } else {
        $email = $getFromU->checkInput($email);
        $screenName = $getFromU->checkInput($screenName);
        $password = $getFromU->checkInput($password);
        $usertype = $getFromU->checkInput($usertype);

        if (!filter_var($email)) {
            $error = 'Invalid email format';
        } else if (strlen($screenName) > 20) {
            $error = 'Name must be between 6-20 characters';
        } else if (strlen($password) < 5) {
            $error = 'Password is too short';
        } else {
            if ($getFromU->checkEmail($email) === true) {
                $error = 'Email is already in use';
            } else {
                $user_id = $getFromU->create('users', array(
                    'email' => $email,
                    'password' => md5($password),
                    'screenName' => $screenName,
                    'usertype' => $usertype,
                    'profileImage' => 'assets/images/defaultProfileImage.png',
                    'profileCover' => 'assets/images/defaultCoverImage.png'
                ));
                $_SESSION['user_id'] = $user_id;
                header('Location: includes/signup.php?step=1');
            }
        }
    }
}

?>
<form method="post" autocomplete="off">
    <?php
    if (isset($error)) {
        echo '<div class="alert alert-danger" role="alert" style="width: 300px; margin:20px auto;text-align:center;">
              ' . $error . '
            </div>';
    }
    ?>
   
    <div class="signup-form">
        <div class="form-group">
            <p style="text-align:left; margin-top:20px;">
                Name
            </p>
            <input class="form-control" type="text" name="screenName" placeholder="Full Name" />
        </div>
        
        <div class="form-group">
            <p style="text-align:left; margin-top:20px;">
                Email
            </p>
            <input class="form-control" type="email" name="email" placeholder="Email" />
        </div>

        <div class="form-group">
            <p style="text-align:left; margin-top:20px;">
                Password
            </p>
            <input class="form-control" type="password" name="password" placeholder="Password" />
        </div>

        <div class="form-group">
            <p style="text-align:left; margin-top:20px;">
                Sign Up As
            </p>
            <label>
                <input type="radio" name="usertype" value="Graduate" required> Graduate
            </label>
            <label style="margin-left: 20px;">
                <input type="radio" name="usertype" value="Company" required> Landlord
            </label>
        </div>

        <input class="new-btn m-auto mt-5" type="submit" name="signup" Value="Signup">
    </div>
</form>
<script type="text/javascript">
    setTimeout(function() {
        // Closing the alert 
        $('#alert').alert('close');
    }, 3500);
</script>
