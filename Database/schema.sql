CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('landlord', 'tenant') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE houses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    landlord_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255), // Path to image
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'available',
    FOREIGN KEY (landlord_id) REFERENCES users(id)
);
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    house_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    commission DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES users(id),
    FOREIGN KEY (house_id) REFERENCES houses(id)
);
