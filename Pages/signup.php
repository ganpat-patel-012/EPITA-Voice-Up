<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voice Up</title>

    <link rel="stylesheet" href="../CSS/headerFooter.css">
    <link rel="stylesheet" href="../CSS/homePage.css">
    <link rel="stylesheet" href="../CSS/signup.css">
    
    
</head>
<body>

    <header>
        <div class="logo">
            <img src="../images/logo-vu.png" alt="Voice Up Logo">
        </div>
        <nav>
            <a href="../index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="offer.php">What We Offer</a>
            <a href="contact.php">Contact Us</a>
        </nav>
    </header>

    <div class="main-container">

    <div class="form-container">
        <h2>Create an Account</h2>
        <form method="POST" action="register.php">
            <div class="form-group">
                <input type="text" name="u_firstname" placeholder="First Name" required>
                <input type="text" name="u_lastname" placeholder="Last Name" required>
            </div>
            <div class="form-group">
                <input type="email" name="u_email" placeholder="Email Address" required>
                <input type="text" name="u_phone" placeholder="Phone Number">
            </div>
            <div class="form-group">
                <input type="password" name="u_pass" placeholder="Password (min. 8 characters)" required>
                <input type="text" name="u_pincode" placeholder="Pincode" required>
            </div>
            <div class="form-group">
                <input type="text" name="u_street" placeholder="Street Address" required>
                <input type="text" name="u_city" placeholder="City" required>
            </div>
            <input type="submit" value="Register">
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>

        <!-- Display message -->
        <?php if (!empty($message)): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
    </div>
    </div>

    <footer>
        Copyright © 2025 Voice Up
    </footer>

</body>
</html>

