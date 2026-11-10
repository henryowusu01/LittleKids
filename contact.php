<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Discover the vision and mission behind Little Kids Transport and learn how we support safer school journeys.">
    <title>Explore | Little Kids Transport</title>
    <link rel="stylesheet" href="Style.css">
    
</head>
<body class="explore-page">
    <header class="explore-header">
        <a class="explore-logo" href="index.php">Little Kids Transport</a>

        <nav aria-label="Main navigation">
            <a href="index.php">Home</a>
            <a href="#our-story">Our mission</a>
            <a href="#contact">Contact</a>
            <a href="LKLogIn.php">Sign in</a>
        </nav>
    </header>

    <main>
        <section class="explore-hero">
            <div class="explore-hero__copy">
                <p class="explore-eyebrow">Every journey matters</p>
                <h1>Little journeys.<br>Big peace of mind.</h1>
                <p class="explore-lead">
                    We help families and schools make children's daily journeys
                    safer, more dependable, and easier to follow.
                </p>
                <a class="explore-button" href="#our-story">Discover what drives us</a>
            </div>
            <div class="explore-hero__image">
                <img src="images/LK.jpeg" alt="Little Kids Transport">
            </div>
        </section>

        <section class="explore-story" id="our-story">
            <div class="explore-section-heading">
                <p class="explore-eyebrow">Who we are</p>
                <h2>Care at the heart of every trip</h2>
                <p>
                    A school journey is more than getting from one place to another.
                    It is part of a child's day and a parent's peace of mind. Little
                    Kids Transport brings guardians, schools, and transport teams
                    together around that responsibility.
                </p>
            </div>

            <div class="explore-principles">
                <article class="explore-principle">
                    <span class="explore-principle__number" aria-hidden="true">01</span>
                    <h3>Our vision</h3>
                    <p>
                        A future where every child can travel to and from school
                        safely, while every family feels informed and confident.
                    </p>
                </article>
                <article class="explore-principle">
                    <span class="explore-principle__number" aria-hidden="true">02</span>
                    <h3>Our mission</h3>
                    <p>
                        To support safer, more reliable school transport through
                        clear journey information and stronger cooperation between
                        guardians, schools, and transport teams.
                    </p>
                </article>
                <article class="explore-principle">
                    <span class="explore-principle__number" aria-hidden="true">03</span>
                    <h3>What guides us</h3>
                    <p>
                        Child-first care, dependable service, open communication,
                        and shared responsibility guide every journey we support.
                    </p>
                </article>
            </div>
        </section>

        <section class="explore-contact" id="contact">
            <div class="explore-contact__copy">
                <p class="explore-eyebrow">We are here to help</p>
                <h2>Let’s make every school journey a little easier.</h2>
                <p>
                    For questions about a specific route or school service, please
                    contact your school’s transport office. Guardians can sign in
                    to view transport information or register for an account.
                </p>
            </div>
            <div class="explore-contact__actions">
                <a class="explore-button" href="LKLogIn.php">Guardian sign in</a>
                <a class="explore-button explore-button--light" href="register.php">Create an account</a>
            </div>
        </section>
    </main>

    <footer class="explore-footer">
        <p>&copy; <?php echo date('Y'); ?> Little Kids Transport. Caring for every mile.</p>
        <a href="index.php">Back to home</a>
    </footer>
    
    <script src="Script.js"></script>
</body>
</html>
