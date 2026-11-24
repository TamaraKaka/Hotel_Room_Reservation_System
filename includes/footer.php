</main>

<footer class="footer">

    <div class="footer-content">

        <div class="footer-section">

            <h3>Hotel Reservation</h3>

            <p>
                Your comfort is our priority.
                Enjoy a convenient and comfortable stay with us.
            </p>

        </div>

        <div class="footer-section">

            <h3>Quick Links</h3>

            <ul>

                <li>
                    <a href="<?= htmlspecialchars(base_url('search/')) ?>">
                        Home
                    </a>
                </li>

                <li>
                    <a href="<?= htmlspecialchars(base_url('search/')) ?>">
                        Search Rooms
                    </a>
                </li>

                <li>
                    <a href="<?= htmlspecialchars(base_url('about.php')) ?>">
                        About Us
                    </a>
                </li>

                <li>
                    <a href="<?= htmlspecialchars(base_url('contact.php')) ?>">
                        Contact
                    </a>
                </li>

            </ul>

        </div>

        <div class="footer-section">

            <h3>Contact Us</h3>

            <p>Email: info@hotelreservation.com</p>

            <p>Phone: +254 700 000 000</p>

        </div>

    </div>

    <div class="footer-bottom">

        <p>
            &copy; <?= date('Y') ?>
            Hotel Reservation System. All rights reserved.
        </p>

    </div>

</footer>

</body>
</html>