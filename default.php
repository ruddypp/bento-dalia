<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bento Kopi - Kopi dari Hati</title>
    <style>
        :root {
            --primary-color: #fff;
            --secondary-color: #4CAF50;
            --text-color: #333;
            --accent-color: #2E7D32;
            --light-gray: #f5f5f5;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            background-color: var(--primary-color);
            color: var(--text-color);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        body.menu-open {
            overflow: hidden;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        header {
            background-color: var(--primary-color);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--secondary-color);
            z-index: 1001;
        }
        
        nav {
            position: relative;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            transition: color 0.3s;
            padding: 5px 0;
            position: relative;
        }
        
        nav ul li a:hover {
            color: var(--secondary-color);
        }
        
        nav ul li a.active {
            color: var(--secondary-color);
        }
        
        nav ul li a.active::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background-color: var(--secondary-color);
            bottom: 0;
            left: 0;
        }
        
        .mobile-menu {
            display: none;
            cursor: pointer;
            z-index: 1001;
            position: relative;
        }
        
        .mobile-menu div {
            width: 25px;
            height: 3px;
            background-color: var(--text-color);
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        
        .mobile-menu.active div:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }
        
        .mobile-menu.active div:nth-child(2) {
            opacity: 0;
        }
        
        .mobile-menu.active div:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
        }
        
        .hero {
            height: 100vh;
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://source.unsplash.com/random/1200x800/?coffee-shop');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            margin-top: 60px;
            padding: 0 20px;
        }
        
        .hero-content h1 {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .hero-content p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--accent-color);
        }
        
        .section {
            padding: 80px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
            color: var(--secondary-color);
        }
        
        .about-content {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .about-text {
            flex: 1;
        }
        
        .about-image {
            flex: 1;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .about-image img {
            width: 100%;
            height: auto;
        }
        
        .founder-section {
            background-color: var(--light-gray);
        }
        
        .founder-content {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .founder-image {
            flex: 1;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .founder-image img {
            width: 100%;
            height: auto;
        }
        
        .founder-text {
            flex: 1;
        }
        
        .founder-text h3 {
            color: var(--secondary-color);
            margin-bottom: 15px;
        }
        
        .features-section {
            background-color: var(--light-gray);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 40px;
            color: var(--secondary-color);
            margin-bottom: 20px;
        }
        
        .feature-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--secondary-color);
        }
        
        .feature-description {
            color: #666;
        }
        
        .menu-section {
            background-color: var(--light-gray);
        }
        
        .menu-categories {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .menu-category {
            padding: 10px 20px;
            margin: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
            background-color: white;
            transition: all 0.3s;
        }
        
        .menu-category.active {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .menu-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .menu-item {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
        }
        
        .menu-item-image {
            height: 200px;
            overflow: hidden;
        }
        
        .menu-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .menu-item-info {
            padding: 15px;
        }
        
        .menu-item-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .menu-item-price {
            color: var(--secondary-color);
            font-weight: 500;
        }
        
        .menu-item-description {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .outlets-section {
            background-color: white;
        }
        
        .outlets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .outlet-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .outlet-card:hover {
            transform: translateY(-5px);
        }
        
        .outlet-image {
            height: 200px;
            overflow: hidden;
        }
        
        .outlet-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .outlet-info {
            padding: 15px;
        }
        
        .outlet-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--secondary-color);
        }
        
        .outlet-address {
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .outlet-hours {
            font-size: 14px;
            color: #666;
        }
        
        .testimonials-section {
            background-color: white;
            position: relative;
            overflow: hidden;
        }
        
        .testimonials-container {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
            padding: 20px 0;
        }
        
        .testimonials-container::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        .testimonial-card {
            flex: 0 0 auto;
            width: 300px;
            background-color: var(--light-gray);
            border-radius: 10px;
            padding: 20px;
            margin-right: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .testimonial-content {
            margin-bottom: 20px;
            font-style: italic;
            color: #555;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .testimonial-author-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .testimonial-author-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .testimonial-author-info h4 {
            margin: 0;
            color: var(--secondary-color);
        }
        
        .testimonial-author-info p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        
        .scroll-buttons {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .scroll-button {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .scroll-button:hover {
            background-color: var(--accent-color);
        }
        
        footer {
            background-color: var(--secondary-color);
            color: white;
            padding: 30px 0;
            text-align: center;
        }
        
        .contact-section {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .contact-item {
            margin: 10px 15px;
            display: flex;
            align-items: center;
        }
        
        .contact-item i {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .section {
                padding: 60px 0;
            }
            
            .mobile-menu {
                display: block;
            }
            
            nav ul {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100vh;
                background-color: var(--primary-color);
                flex-direction: column;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            }
            
            nav ul.active {
                display: flex;
            }
            
            nav ul li {
                margin: 15px 0;
            }
            
            nav ul li a {
                font-size: 18px;
            }
            
            nav ul li a.active::after {
                display: none;
            }
            
            nav ul li a.active {
                color: var(--secondary-color);
                font-weight: 700;
            }
            
            .about-content, .founder-content {
                flex-direction: column;
            }
            
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-content p {
                font-size: 1rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .menu-items {
                grid-template-columns: 1fr;
            }
            
            .outlets-grid {
                grid-template-columns: 1fr;
            }
            
            .testimonial-card {
                width: 280px;
            }
            
            .header-container {
                padding: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .hero {
                padding: 0 15px;
            }
            
            .hero-content h1 {
                font-size: 1.8rem;
                padding: 0 10px;
            }
            
            .hero-content p {
                font-size: 0.9rem;
                padding: 0 10px;
            }
            
            .section {
                padding: 50px 0;
            }
            
            .section-title {
                font-size: 1.5rem;
                margin-bottom: 30px;
            }
            
            .testimonial-card {
                width: 260px;
                padding: 15px;
            }
            
            .menu-category {
                padding: 8px 15px;
                margin: 3px;
                font-size: 14px;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container header-container">
            <div class="logo">Bento Kopi</div>
            <nav>
                <ul id="nav-menu">
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#about">Tentang Kami</a></li>
                    <li><a href="#founder">Pendiri</a></li>
                    <li><a href="#features">Keunggulan</a></li>
                    <li><a href="#menu">Menu</a></li>
                    <li><a href="#outlets">Outlet</a></li>
                    <li><a href="#testimonials">Testimoni</a></li>
                    <li><a href="#contact">Kontak</a></li>
                </ul>
                <div class="mobile-menu" id="mobile-menu">
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </nav>
        </div>
    </header>

    <section id="home" class="hero">
        <div class="hero-content">
            <h1>Bento Kopi</h1>
            <p>Dari Hati untuk Pecinta Kopi Indonesia</p>
            <a href="#about" class="btn">Tentang Kami</a>
        </div>
    </section>

    <section id="about" class="section">
        <div class="container">
            <h2 class="section-title">Tentang Bento Kopi</h2>
            <div class="about-content">
                <div class="about-text">
                    <p>Bento Kopi adalah jaringan kedai kopi yang didirikan oleh Hairul Umam Bento, seorang wirausahawan muda asal Sumenep, Madura. Didirikan pada tahun 2012, Bento Kopi telah berkembang menjadi salah satu kedai kopi terkemuka di Indonesia.</p>
                    <p>Dengan modal awal hanya Rp7 juta, Hairul berhasil mengembangkan bisnisnya hingga memiliki lebih dari 70 outlet yang tersebar di berbagai kota di Indonesia.</p>
                    <p>Bento Kopi dikenal dengan konsep kedai kopi yang nyaman dengan desain interior yang menarik, baik untuk area indoor maupun outdoor. Hal ini menjadikan Bento Kopi sebagai tempat favorit untuk belajar, bekerja, atau sekadar bersantai bersama teman.</p>
                </div>
                <div class="about-image">
                    <img src="https://source.unsplash.com/random/600x400/?coffee-shop" alt="Bento Kopi">
                </div>
            </div>
        </div>
    </section>

    <section id="founder" class="section founder-section">
        <div class="container">
            <h2 class="section-title">Kisah Pendiri</h2>
            <div class="founder-content">
                <div class="founder-image">
                    <img src="https://source.unsplash.com/random/600x400/?entrepreneur" alt="Hairul Umam Bento">
                </div>
                <div class="founder-text">
                    <h3>Hairul Umam Bento</h3>
                    <p>Lahir pada tahun 1992 di Sumenep, Madura, Hairul Umam Bento memulai perjalanannya di dunia bisnis saat masih menjadi mahasiswa Manajemen di Fakultas Ekonomi Universitas Islam Indonesia (UII) pada tahun 2012.</p>
                    <p>Dengan modal awal sebesar Rp7 juta—Rp5 juta dari pinjaman orang tua dan Rp2 juta dari pengembalian dana beasiswa—Hairul memulai usahanya dengan mengubah sebuah rumah makan yang hampir bangkrut menjadi kafe yang juga berfungsi sebagai tempat kursus bahasa Inggris.</p>
                    <p>Meskipun pernah mengalami kegagalan dalam usaha rumah makan Padang, Hairul tidak menyerah dan terus mengembangkan bisnisnya. Kini, Bento Kopi telah berkembang pesat dengan lebih dari 70 outlet yang tersebar di berbagai kota di Indonesia.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="section features-section">
        <div class="container">
            <h2 class="section-title">Keunggulan Bento Kopi</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <h3 class="feature-title">Menu Variatif dan Terjangkau</h3>
                    <p class="feature-description">Bento Kopi menawarkan berbagai pilihan menu dengan harga yang bersahabat, menjadikannya pilihan populer di kalangan mahasiswa dan keluarga muda.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-couch"></i>
                    </div>
                    <h3 class="feature-title">Suasana yang Menyenangkan</h3>
                    <p class="feature-description">Desain interior yang nyaman dengan pilihan tempat duduk outdoor dan indoor menciptakan pengalaman nongkrong yang menyenangkan bagi pelanggan.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3 class="feature-title">Pelayanan Profesional</h3>
                    <p class="feature-description">Karyawan yang terlatih memberikan pelayanan ramah dan profesional, memastikan kepuasan pelanggan.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="menu" class="section menu-section">
        <div class="container">
            <h2 class="section-title">Menu Kami</h2>
            <div class="menu-categories">
                <div class="menu-category active" data-category="coffee">Kopi</div>
                <div class="menu-category" data-category="non-coffee">Non-Kopi</div>
                <div class="menu-category" data-category="food">Makanan</div>
            </div>
            <div class="menu-items" id="coffee-menu">
                <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="https://source.unsplash.com/random/300x200/?espresso" alt="Espresso">
                    </div>
                    <div class="menu-item-info">
                        <div class="menu-item-title">Espresso</div>
                        <div class="menu-item-price">Rp 18.000</div>
                        <div class="menu-item-description">Pilihan klasik bagi pecinta kopi hitam pekat.</div>
                    </div>
                </div>
                <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="https://source.unsplash.com/random/300x200/?americano" alt="Americano">
                    </div>
                    <div class="menu-item-info">
                        <div class="menu-item-title">Americano</div>
                        <div class="menu-item-price">Rp 20.000</div>
                        <div class="menu-item-description">Kombinasi espresso dengan air panas, memberikan rasa yang lebih ringan.</div>
                    </div>
                </div>
                <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="https://source.unsplash.com/random/300x200/?latte" alt="Latte">
                    </div>
                    <div class="menu-item-info">
                        <div class="menu-item-title">Latte</div>
                        <div class="menu-item-price">Rp 25.000</div>
                        <div class="menu-item-description">Espresso yang dipadukan dengan susu panas, menghasilkan rasa lembut dan creamy.</div>
                    </div>
                </div>
                <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="https://source.unsplash.com/random/300x200/?cappuccino" alt="Cappuccino">
                    </div>
                    <div class="menu-item-info">
                        <div class="menu-item-title">Cappuccino</div>
                        <div class="menu-item-price">Rp 25.000</div>
                        <div class="menu-item-description">Mirip dengan latte, namun dengan busa susu yang lebih tebal.</div>
                    </div>
                </div>
            </div>
            
            <div class="menu-items" id="non-coffee-menu" style="display: none;">
                <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="https://source.unsplash.com/random/300x200/?mango-smoothie" alt="Mango Smoothie">
                    </div>
                    <div class="menu-item-info">
                        <div class="menu-item-title">Mango Smoothie</div>
                        <div class="menu-item-price">Rp 22.000</div>
                        <div class="menu-item-description">Minuman segar dengan rasa mangga yang manis alami.</div>
                    </div>
                </div>
                <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="https://source.unsplash.com/random/300x200/?strawberry-milk" alt="Strawberry Milk">
                    </div>
                    <div class="menu-item-info">
                        <div class="menu-item-title">Strawberry Milk</div>
                        <div class="menu-item-price">Rp 20.000</div>
                        <div class="menu-item-description">Perpaduan susu dan stroberi, cocok untuk yang menyukai rasa manis dan creamy.</div>
                    </div>
                </div>
                <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="https://source.unsplash.com/random/300x200/?matcha-latte" alt="Matcha Latte">
                    </div>
                    <div class="menu-item-info">
                        <div class="menu-item-title">Matcha Latte</div>
                        <div class="menu-item-price">Rp 25.000</div>
                        <div class="menu-item-description">Teh hijau matcha yang dipadukan dengan susu, memberikan rasa yang khas.</div>
                    </div>
                </div>
                <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="https://source.unsplash.com/random/300x200/?chocolate" alt="Hot Chocolate">
                    </div>
                    <div class="menu-item-info">
                        <div class="menu-item-title">Hot Chocolate</div>
                        <div class="menu-item-price">Rp 23.000</div>
                        <div class="menu-item-description">Cokelat panas dengan rasa yang kaya dan lembut.</div>
                    </div>
                </div>
            </div>
            
            <div class="menu-items" id="food-menu" style="display: none;">
                <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="https://source.unsplash.com/random/300x200/?chicken-katsu" alt="Chicken Katsu">
                    </div>
                    <div class="menu-item-info">
                        <div class="menu-item-title">Chicken Katsu</div>
                        <div class="menu-item-price">Rp 35.000</div>
                        <div class="menu-item-description">Potongan ayam yang dibalut tepung roti dan digoreng hingga renyah.</div>
                    </div>
                </div>
                <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="https://source.unsplash.com/random/300x200/?spicy-noodles" alt="Mie Iblis">
                    </div>
                    <div class="menu-item-info">
                        <div class="menu-item-title">Mie Iblis</div>
                        <div class="menu-item-price">Rp 30.000</div>
                        <div class="menu-item-description">Mie dengan rasa pedas yang menggugah selera, cocok bagi pecinta makanan pedas.</div>
                    </div>
                </div>
                <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="https://source.unsplash.com/random/300x200/?chicken-egg" alt="Chicken Egg Sambal Dabu-Dabu">
                    </div>
                    <div class="menu-item-info">
                        <div class="menu-item-title">Chicken Egg Sambal Dabu-Dabu</div>
                        <div class="menu-item-price">Rp 32.000</div>
                        <div class="menu-item-description">Kombinasi ayam, telur, dan sambal dabu-dabu yang segar dan pedas.</div>
                    </div>
                </div>
                <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="https://source.unsplash.com/random/300x200/?pasta" alt="Pasta Carbonara">
                    </div>
                    <div class="menu-item-info">
                        <div class="menu-item-title">Pasta Carbonara</div>
                        <div class="menu-item-price">Rp 38.000</div>
                        <div class="menu-item-description">Pasta dengan saus krim, telur, keju parmesan, dan potongan daging asap.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="outlets" class="section outlets-section">
        <div class="container">
            <h2 class="section-title">Outlet Kami</h2>
            <div class="outlets-grid">
                <div class="outlet-card">
                    <div class="outlet-image">
                        <img src="https://source.unsplash.com/random/300x200/?yogyakarta" alt="Bento Kopi Yogyakarta">
                    </div>
                    <div class="outlet-info">
                        <div class="outlet-name">Bento Kopi Yogyakarta</div>
                        <div class="outlet-address">Jl. Kaliurang KM 5, Yogyakarta</div>
                        <div class="outlet-hours">Buka: 10.00 - 23.00 WIB</div>
                    </div>
                </div>
                <div class="outlet-card">
                    <div class="outlet-image">
                        <img src="https://source.unsplash.com/random/300x200/?jakarta" alt="Bento Kopi Jakarta">
                    </div>
                    <div class="outlet-info">
                        <div class="outlet-name">Bento Kopi Jakarta</div>
                        <div class="outlet-address">Jl. Kemang Raya No. 10, Jakarta Selatan</div>
                        <div class="outlet-hours">Buka: 10.00 - 23.00 WIB</div>
                    </div>
                </div>
                <div class="outlet-card">
                    <div class="outlet-image">
                        <img src="https://source.unsplash.com/random/300x200/?bandung" alt="Bento Kopi Bandung">
                    </div>
                    <div class="outlet-info">
                        <div class="outlet-name">Bento Kopi Bandung</div>
                        <div class="outlet-address">Jl. Dago No. 15, Bandung</div>
                        <div class="outlet-hours">Buka: 10.00 - 23.00 WIB</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="testimonials" class="section testimonials-section">
        <div class="container">
            <h2 class="section-title">Apa Kata Mereka</h2>
            <div class="testimonials-container" id="testimonials-container">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"Bento Kopi adalah tempat favorit saya untuk mengerjakan tugas kuliah. Suasananya nyaman, kopinya enak, dan harganya terjangkau untuk mahasiswa seperti saya."</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="testimonial-author-image">
                            <img src="https://source.unsplash.com/random/100x100/?portrait-woman" alt="Dewi Sartika">
                        </div>
                        <div class="testimonial-author-info">
                            <h4>Dewi Sartika</h4>
                            <p>Mahasiswa</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"Saya sering mengadakan meeting dengan klien di Bento Kopi. Tempatnya cozy, menu kopinya bervariasi, dan makanannya juga enak-enak. Sangat direkomendasikan!"</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="testimonial-author-image">
                            <img src="https://source.unsplash.com/random/100x100/?portrait-man" alt="Budi Santoso">
                        </div>
                        <div class="testimonial-author-info">
                            <h4>Budi Santoso</h4>
                            <p>Pengusaha</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"Sebagai pecinta kopi, saya sangat mengapresiasi kualitas kopi di Bento Kopi. Barista mereka sangat terampil dan ramah dalam memberikan rekomendasi."</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="testimonial-author-image">
                            <img src="https://source.unsplash.com/random/100x100/?portrait-woman-2" alt="Siti Nurhaliza">
                        </div>
                        <div class="testimonial-author-info">
                            <h4>Siti Nurhaliza</h4>
                            <p>Coffee Enthusiast</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"Mie Iblis di Bento Kopi adalah yang terpedas dan terenak yang pernah saya coba. Selalu jadi menu favorit saat berkunjung ke sini."</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="testimonial-author-image">
                            <img src="https://source.unsplash.com/random/100x100/?portrait-man-2" alt="Reza Rahadian">
                        </div>
                        <div class="testimonial-author-info">
                            <h4>Reza Rahadian</h4>
                            <p>Food Blogger</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="scroll-buttons">
                <button class="scroll-button" id="scroll-left">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="scroll-button" id="scroll-right">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>

    <footer id="contact">
        <div class="container">
            <div class="contact-section">
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>info@bentokopi.com</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span>+62 812 3456 7890</span>
                </div>
                <div class="contact-item">
                    <i class="fab fa-instagram"></i>
                    <span>@bentokopiindonesia</span>
                </div>
            </div>
            <p>&copy; <?php echo date('Y'); ?> Bento Kopi. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        // Set initial active state for navigation
        document.addEventListener('DOMContentLoaded', function() {
            // Set the first menu item as active by default
            const firstNavLink = document.querySelector('#nav-menu li a');
            if (firstNavLink) {
                firstNavLink.classList.add('active');
            }
            
            // Show coffee menu by default
            const coffeeMenu = document.getElementById('coffee-menu');
            if (coffeeMenu) {
                coffeeMenu.style.display = 'grid';
            }
        });
    
        // Mobile menu functionality
        const mobileMenu = document.getElementById('mobile-menu');
        const navMenu = document.getElementById('nav-menu');
        
        mobileMenu.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
        
        // Close mobile menu when clicking on a menu item
        const navLinks = document.querySelectorAll('#nav-menu li a');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Get the target element
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    // Prevent default anchor behavior
                    e.preventDefault();
                    
                    // Close the mobile menu
                    navMenu.classList.remove('active');
                    mobileMenu.classList.remove('active');
                    document.body.classList.remove('menu-open');
                    
                    // Remove active class from all links
                    navLinks.forEach(navLink => {
                        navLink.classList.remove('active');
                    });
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Calculate position to scroll to (with offset for header)
                    const headerHeight = document.querySelector('header').offsetHeight;
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight;
                    
                    // Scroll to the target
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Menu category functionality
        const menuCategories = document.querySelectorAll('.menu-category');
        const menuItems = document.querySelectorAll('.menu-items');
        
        menuCategories.forEach(category => {
            category.addEventListener('click', function() {
                const categoryType = this.getAttribute('data-category');
                
                // Remove active class from all categories
                menuCategories.forEach(cat => cat.classList.remove('active'));
                
                // Add active class to clicked category
                this.classList.add('active');
                
                // Hide all menu items
                menuItems.forEach(item => {
                    item.style.display = 'none';
                });
                
                // Show selected menu items
                document.getElementById(`${categoryType}-menu`).style.display = 'grid';
            });
        });
        
        // Testimonial scroll functionality
        document.getElementById('scroll-left').addEventListener('click', function() {
            document.getElementById('testimonials-container').scrollBy({
                left: -320,
                behavior: 'smooth'
            });
        });
        
        document.getElementById('scroll-right').addEventListener('click', function() {
            document.getElementById('testimonials-container').scrollBy({
                left: 320,
                behavior: 'smooth'
            });
        });
        
        // Add active class to menu item based on scroll position
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section');
            const scrollPosition = window.scrollY;
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                const sectionHeight = section.offsetHeight;
                const sectionId = section.getAttribute('id');
                
                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === `#${sectionId}`) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html> 