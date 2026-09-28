<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['aemail'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: alogin.php');
  }

// Validates an uploaded photo and stores it as Images/bikes/<bike_id>.<ext>.
// Returns the stored filename, 'placeholder.svg' if no file was chosen,
// or false if the upload is not an acceptable image.
function save_bike_image($file, $bikeid) {
  $allowed = array(
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG  => 'png',
    IMAGETYPE_GIF  => 'gif',
    IMAGETYPE_WEBP => 'webp',
  );

  if (!is_array($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    return 'placeholder.svg';
  }

  if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) {
    return false;
  }

  // Judge the upload by its decoded image header, never by the client
  // supplied filename or MIME type.
  $info = @getimagesize($file['tmp_name']);
  if ($info === false || !isset($allowed[$info[2]])) {
    return false;
  }

  $safeid = preg_replace('/[^0-9A-Za-z_-]/', '', $bikeid);
  $name = $safeid . '.' . $allowed[$info[2]];

  if (!move_uploaded_file($file['tmp_name'], dirname(__DIR__) . '/Images/bikes/' . $name)) {
    return false;
  }

  return $name;
}

$query1="SELECT term_id,term_name FROM terminal";
$result1=mysqli_query($conn,$query1);

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>َBike Entry</title>
    <link rel="stylesheet" href="../css/bikeentry.css">
    <link rel="stylesheet" type="text/css" href="../css/navbar.css">
    <link rel="stylesheet" type="text/css" href="../css/footer.css">
  </head>
  <body>


    <!-- NAVIGATION -->

    <nav>
      <a href="adminpage.php"><img src="../images/logo.png" class="logo" alt="logo"></a>
      <ul>
        <li><a style="color:white" href="">ADMIN</a></li>
        <!-- <li><a href="">FEEDBACK</a></li>
        <li><a href="">CONTACT US</a></li> -->
        <li><a style="color:white" href="alogout.php">LOGOUT</a></li>
      </ul>
    </nav>
  
  <!-- END OF NAVIGATION -->
<div class="page-wrapper">


<form class="box" method="post" enctype="multipart/form-data">
  <h1>ADD BIKE</h1>
  <h2>Enter Bike ID</h2>
  <input type="text" name="bike_id" placeholder="Bike ID" required>
  <h2>Enter Bike Name</h2>
   <input type="text" name="bname" placeholder="Bike Name" required>
   <h2>Enter Model Year</h2>
    <input type="number" name="model" placeholder="Model Year" required>
    <h2>Enter Bike Color</h2>
     <input type="text" name="color" placeholder="Colour" required>
     <h2>Upload Bike Photo</h2>
     <input type="file" name="bikeimg" accept="image/png,image/jpeg,image/gif,image/webp">
     <h2>Enter Bike Type</h2>
     <select class="bike_type" name="btype" required>
       <option value="">choose..</option>
       <option value="bike">BIKE</option>
       <option value="scooter">SCOOTER</option>
     </select>
     <h2>Enter Price (Rs. /hour)</h2>
     <input type="number" name="price" placeholder="Rental Price in NPR" min="1" required>
     <h2>Select Terminal</h2>
     <select class="terminalid" name="tname" required>
       <option value="">choose</option>


       <!-- php code for dropdown  -->
       <?php
       while($row1=$result1->fetch_assoc() )
        {
          $menu=$row1['term_name'];
          $men=$row1['term_id'];
          
          echo "<option value='$men'>$menu</option>";
        }
        ?>
       <!-- end of dropdown insertion  -->
       
     </select>
  <input type="submit" name="" value="ADD BIKE">
</form>

<!-- DATABSE INSERTION -->

<?php
if (isset($_POST) & !empty($_POST)) {
  // receive all input values from the form
  $bikeid = mysqli_real_escape_string($conn, $_POST['bike_id']);
  $bname = mysqli_real_escape_string($conn, $_POST['bname']);
  $model = mysqli_real_escape_string($conn, $_POST['model']);
  $color = mysqli_real_escape_string($conn, $_POST['color']);
  $btype = mysqli_real_escape_string($conn, $_POST['btype']);
  $price = mysqli_real_escape_string($conn, $_POST['price']);
  $tname = mysqli_real_escape_string($conn, $_POST['tname']);
 
 // first check the database to make sure 
  // a user does not already exist with the same username and/or email
  $user_check_query = "SELECT * FROM bike WHERE bike_id='$bikeid'";
  $result2 = mysqli_query($conn, $user_check_query) or die(mysqli_error($conn));
  $user = mysqli_num_rows($result2);
  
  if ($user==1) { // if user exists
    //if ($user['email'] == $email) {
      echo "<script type='text/javascript'>alert('Bike already exists')</script>";
    header("refresh:1;url=bikeentry.php");  
   //} 
  }

  // Finally, register user if there are no errors in the form
  else {
  //encrypt the password before saving in the database

    $image = save_bike_image($_FILES['bikeimg'], $bikeid);

    if ($image === false) {
      echo "<script type='text/javascript'>alert('Image not saved. Please upload a JPG, PNG, GIF or WEBP file under 2 MB.')</script>";
    }
    else {
      $img = mysqli_real_escape_string($conn, $image);

      $query2 = "INSERT INTO bike (bike_id, bike_name, image, model, color, bike_type,price, term_id) 
            VALUES('$bikeid','$bname', '$img', '$model','$color', '$btype','$price', '$tname')";
      mysqli_query($conn, $query2);
      echo "<script type='text/javascript'>alert('Bike Added Succesfully')</script>";
      // header('refresh:1;url=adminpage.php');
    }
  }
}

?>
<!-- DATABASE INSERTION ENDED -->
</div>

<!-- FOOTER STARTS HERE -->

<?php
include 'footer.php';
?>
<!-- Footer ends here -->


  </body>
</html>
