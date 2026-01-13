CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(191) UNIQUE NULL,
    phone VARCHAR(32) UNIQUE NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(191) NULL,
    role ENUM('client','admin') NOT NULL DEFAULT 'client',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('new','confirmed','in_work','ready','canceled') NOT NULL DEFAULT 'new',
    total_rub DECIMAL(10,2) NOT NULL DEFAULT 0,
    customer_comment TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    qty INT NOT NULL,
    price_rub DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    duration_min INT NOT NULL,
    price_from_rub DECIMAL(10,2) NOT NULL DEFAULT 0
);

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    customer_name VARCHAR(191) NOT NULL,
    customer_phone VARCHAR(32) NOT NULL,
    car_info VARCHAR(255) NULL,
    service_id INT NOT NULL,
    start_at DATETIME NOT NULL,
    status ENUM('new','confirmed','done','canceled') NOT NULL DEFAULT 'new',
    comment TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
);
