
-- Create database
CREATE DATABASE IF NOT EXISTS mercy_pastries_db;
USE mercy_pastries_db;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    bio TEXT,
    profile_picture VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2),
    image VARCHAR(255),
    gallery TEXT,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Services table
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    price_range VARCHAR(100),
    image VARCHAR(255),
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    shipping_address TEXT,
    phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Contact messages table
CREATE TABLE contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Site settings table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'textarea', 'image', 'email', 'phone') DEFAULT 'text'
);

-- Hero slides table
CREATE TABLE hero_slides (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100),
    subtitle VARCHAR(200),
    button_text VARCHAR(50),
    button_link VARCHAR(255),
    image VARCHAR(255),
    order_position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (full_name, email, password, role) VALUES 
('Admin', 'admin@mercypastries.com', '$2y$10$YourHashedPasswordHere', 'admin');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type) VALUES
('site_name', 'Mercy Pastries', 'text'),
('site_email', 'info@mercypastries.com', 'email'),
('site_phone', '+256 706 083004', 'phone'),
('site_address', 'Katabi – Entebbe Road, Kampala, Uganda', 'text'),
('facebook_url', '#', 'text'),
('instagram_url', '#', 'text'),
('whatsapp_url', '#', 'text'),
('tiktok_url', '#', 'text'),
('about_text', 'Welcome to Mercy Pastries – Where every bite tells a story of love and flavor.', 'textarea');

-- Insert sample categories
INSERT INTO categories (name, slug, description) VALUES
('Cakes', 'cakes', 'Delicious celebration cakes for every occasion'),
('Pastries', 'pastries', 'Fresh daily pastries including croissants, danishes, and more'),
('Cupcakes', 'cupcakes', 'Beautiful cupcakes for parties and events'),
('Bread', 'bread', 'Freshly baked bread daily'),
('Cookies', 'cookies', 'Homestyle cookies and biscuits');

-- Insert sample services
INSERT INTO services (name, slug, description, price_range, icon) VALUES
('Custom Celebration Cakes', 'custom-cakes', 'Birthday, wedding, anniversary, baby shower – designed exactly to your theme and taste.', 'UGX 120,000+', 'fa-birthday-cake'),
('Fresh Daily Pastries', 'daily-pastries', 'Croissants, donuts, cinnamon rolls, scones, muffins – baked fresh every morning.', 'UGX 5,000 – 18,000', 'fa-bread-slice'),
('Cupcakes & Mini Desserts', 'cupcakes', 'Perfect for events, gifts or just because. Available in boxes of 6, 12 or 24.', 'UGX 48,000 – 180,000', 'fa-cupcake'),
('Island-wide Delivery', 'delivery', 'Fast & careful delivery across Kampala and surrounding areas.', 'UGX 10,000+', 'fa-truck'),
('Baking Workshops', 'workshops', 'Learn to decorate cakes, make macarons or perfect your croissant technique.', 'UGX 150,000 – 350,000', 'fa-chalkboard-teacher'),
('Corporate Catering', 'catering', 'Office meetings, launches, weddings, parties – dessert tables, tiered cakes & more.', 'Custom quotation', 'fa-briefcase');