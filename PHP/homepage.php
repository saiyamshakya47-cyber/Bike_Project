<?php
// connection.php relies on mysqli_connect()'s "or die" pattern, which stopped
// firing on PHP 8.1+ because mysqli throws by default. Suppress the exceptions
// so the shared connection file keeps working untouched.
mysqli_report(MYSQLI_REPORT_OFF);

session_start();
include 'connection.php';   // single, shared database connection
include 'helpers.php';

$is_member = isset($_SESSION['uemail']);

// Optional columns are detected rather than assumed, so the page works both
// before and after the bike-images migration is applied.
$bike_columns = bike_columns($conn);
$has_image    = in_array('image', $bike_columns, true);
$reg_column   = bike_reg_column($bike_columns);

$bikes      = array();
$load_error = '';

$select = array('bike_id', 'bike_name', 'model', 'color', 'bike_type', 'price', 'avail');
if ($has_image) {
    $select[] = 'image';
}

// $reg_column comes from SHOW COLUMNS, never from user input, so interpolating
// the identifier into the SQL is safe.
if ($reg_column !== null) {
    $select[] = '`' . $reg_column . '` AS reg_no';
}

$sql = 'SELECT b.' . implode(', b.', $select) . ', t.term_name
          FROM `bike` b
          LEFT JOIN `terminal` t ON b.term_id = t.term_id
         WHERE b.avail = ?
         ORDER BY b.bike_type, b.bike_name';

$stmt = mysqli_prepare($conn, $sql);
if ($stmt === false) {
    $load_error = 'We could not load the bike list right now.';
} else {
    $avail = 1;
    mysqli_stmt_bind_param($stmt, 'i', $avail);

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $bikes[] = $row;
        }
    } else {
        $load_error = 'We could not load the bike list right now.';
    }

    mysqli_stmt_close($stmt);
}

$bike_count = count($bikes);

// Counts per category, used for the short summary under the section heading.
// Defaults are applied because the keys are absent when a category has no
// available bikes.
$type_counts = array('bike' => 0, 'scooter' => 0);
foreach ($bikes as $bike) {
    $key = strtolower($bike['bike_type']);
    if (!isset($type_counts[$key])) {
        $type_counts[$key] = 0;
    }
    $type_counts[$key]++;
}

$bike_total    = $type_counts['bike'];
$scooter_total = $type_counts['scooter'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bike Rental &mdash; Rent Your Ride</title>
	<link rel="stylesheet" type="text/css" href="../CSS/navbar.css">
	<link rel="stylesheet" type="text/css" href="../CSS/footer.css">
	<link rel="stylesheet" type="text/css" href="../CSS/homepage.css">
</head>

<body class="home">

	<!-- NAVIGATION -->

	<nav class="site-nav">
		<div class="site-nav__inner">
			<a href="homepage.php" class="site-nav__brand">
				<img src="../Images/logo.png" class="logo" alt="Bike Rental">
			</a>

			<button class="site-nav__toggle" type="button" aria-expanded="false" aria-controls="site-nav-menu">
				<span class="site-nav__bar"></span>
				<span class="site-nav__bar"></span>
				<span class="site-nav__bar"></span>
				<span class="site-nav__sr">Toggle navigation</span>
			</button>

			<ul class="site-nav__menu" id="site-nav-menu">
				<li><a href="#about">ABOUT US</a></li>
				<li><a href="#available-bikes">AVAILABLE BIKES</a></li>
				<?php if ($is_member) { ?>
				<li><a class="site-nav__cta" href="mainpage.php"><?php echo h($_SESSION['fname'] . ' ' . $_SESSION['lname']); ?></a></li>
				<?php } else { ?>
				<li><a class="site-nav__cta" href="login.php">MEMBER LOGIN</a></li>
				<?php } ?>
				<li><a href="alogin.php">ADMIN LOGIN</a></li>
			</ul>
		</div>
	</nav>

	<!-- END OF NAVIGATION -->

	<!-- HERO -->

	<header class="hero" id="home">
		<div class="hero__inner">
			<p class="hero__eyebrow">Bike Rental System</p>
			<h1 class="hero__title">Rent Your Ride</h1>
			<p class="hero__subtitle">
				Choose from our available bikes and start your journey.
			</p>

			<a class="hero__cta" href="#available-bikes">EXPLORE BIKES</a>

			<?php if (!$is_member) { ?>
			<p class="hero__note">
				New here? <a href="signup.php">Create an account</a> to book a bike.
				Browsing is open to everyone.
			</p>
			<?php } ?>
		</div>
	</header>

	<!-- END OF HERO -->

	<div class="page-wrapper">

		<!-- ABOUT US -->

		<section class="about" id="about">
			<div class="about__inner">
				<h2 class="section-title">About Us</h2>
				<p>
					Bike Rental is a web based application that serves online booking of
					bikes for rent by choosing the location as per the customer's
					requirement. Browse the fleet below, pick a ride and book it in a
					few steps.
				</p>
				<ul class="about__points">
					<li><strong><?php echo (int) $bike_count; ?></strong> bikes available right now</li>
					<li><strong><?php echo (int) $bike_total; ?></strong> motorcycles</li>
					<li><strong><?php echo (int) $scooter_total; ?></strong> scooters</li>
					<li>Prices quoted in Nepali Rupees (NPR) per hour</li>
				</ul>
			</div>
		</section>

		<!-- END OF ABOUT US -->

		<!-- AVAILABLE BIKES -->

		<section class="bikes" id="available-bikes">
			<div class="bikes__inner">

				<header class="bikes__head">
					<h2 class="section-title">Available Bikes</h2>
					<p class="bikes__intro">
						No account needed to browse. Sign in only when you are ready to rent.
					</p>
				</header>

				<?php if ($load_error !== '') { ?>
				<p class="bikes__empty"><?php echo h($load_error); ?></p>

				<?php } elseif ($bike_count === 0) { ?>
				<p class="bikes__empty">
					Every bike is currently on rent. Please check back shortly.
				</p>

				<?php } else { ?>
				<ul class="bike-grid">

					<?php foreach ($bikes as $bike) {
						$bike_id   = (int) $bike['bike_id'];
						$bike_name = $bike['bike_name'];
						$is_free   = ((int) $bike['avail'] === 1);

						$photo = bike_image(
							$has_image ? $bike['image'] : null,
							$bike_id,
							$bike_name
						);

						$reg_number = ($reg_column !== null && isset($bike['reg_no']))
							? trim((string) $bike['reg_no'])
							: '';
					?>
					<li class="bike-card">

						<div class="bike-card__media">
							<img src="<?php echo h($photo); ?>" alt="<?php echo h($bike_name); ?>">
							<span class="bike-badge <?php echo $is_free ? 'bike-badge--ok' : 'bike-badge--busy'; ?>">
								<?php echo $is_free ? 'Available' : 'On Rent'; ?>
							</span>
						</div>

						<div class="bike-card__body">
							<h3 class="bike-card__name"><?php echo h($bike_name); ?></h3>
							<p class="bike-card__model"><?php echo h($bike['model']); ?> model</p>

							<p class="bike-card__price"><?php echo h(npr_rate($bike['price'])); ?></p>

							<ul class="bike-card__specs">
								<li><span>Type</span><strong><?php echo h(ucfirst($bike['bike_type'])); ?></strong></li>
								<?php if ($reg_number !== '') { ?>
								<li><span>Reg. No</span><strong><?php echo h($reg_number); ?></strong></li>
								<?php } ?>
							</ul>
						</div>

						<div class="bike-card__actions">
							<details class="bike-card__more">
								<summary>View Details</summary>
								<dl>
									<dt>Bike ID</dt><dd><?php echo h($bike_id); ?></dd>
									<dt>Model year</dt><dd><?php echo h($bike['model']); ?></dd>
									<dt>Colour</dt><dd><?php echo h(ucfirst($bike['color'])); ?></dd>
									<dt>Type</dt><dd><?php echo h(ucfirst($bike['bike_type'])); ?></dd>
									<?php if ($reg_number !== '') { ?>
									<dt>Reg. No</dt><dd><?php echo h($reg_number); ?></dd>
									<?php } ?>
									<dt>Pickup terminal</dt>
									<dd><?php echo h($bike['term_name'] !== null ? $bike['term_name'] : 'To be confirmed'); ?></dd>
									<dt>Rental rate</dt><dd><?php echo h(npr_rate($bike['price'])); ?></dd>
									<dt>Status</dt><dd><?php echo $is_free ? 'Available now' : 'Currently on rent'; ?></dd>
								</dl>
							</details>

							<?php if ($is_member) { ?>
							<form action="booking.php" method="post" class="bike-card__form">
								<input type="hidden" name="bikeid" value="<?php echo $bike_id; ?>">
								<button type="submit" class="bike-card__rent">RENT NOW</button>
							</form>
							<?php } else { ?>
							<a class="bike-card__rent" href="login.php">RENT NOW</a>
							<?php } ?>
						</div>

					</li>
					<?php } ?>
				</ul>
				<?php } ?>

			</div>
		</section>

		<!-- END OF AVAILABLE BIKES -->

	</div>

	<!-- FOOTER STARTS HERE -->

<?php
include 'footer.php';
?>
<!-- FOOTER ENDS HERE -->

	<script src="../JS/nav-toggle.js"></script>

</body>
</html>
