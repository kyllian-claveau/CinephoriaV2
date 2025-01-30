-- Insertion dans la table cinema
INSERT INTO cinema (name, location) VALUES ('Cinéma de Lyon', 'Lyon');

-- Insertion dans la table film
INSERT INTO film (title, film_filename, description, age_min, duration, is_favorite, created_at)
VALUES ('Intouchable', 'inception.mp4', 'Un film très touchant', 10, 112, 1, NOW());

-- Insertion dans la table genre
INSERT INTO genre (name) VALUES ('Drame');

-- Insertion dans la table room
INSERT INTO room (number, quality, rows_room, columns_room, total_seats, accessible_seats, stairs)
VALUES (1, 'IMAX', 10, 15, 150, '[]', '[]');

-- Insertion dans la table reparation
INSERT INTO reparation (description, statut, date_creation, date_reparation, room_id)
VALUES ('Réparation du projecteur', 'En cours', NOW(), NULL, 1);

-- Insertion dans la table session
INSERT INTO session (reserved_seats, start_date, end_date, price, film_id, cinema_id, room_id)
VALUES ('[]', '2025-02-01 14:00:00', '2025-02-01 16:30:00', 12.50, 1, 1, 1);

-- Insertion dans la table user
INSERT INTO user (confirmation_token, is_active, is_temporary_password, email, firstname, lastname, roles, password)
VALUES (NULL, 1, 0, 'user@example.com', 'John', 'Doe', '["ROLE_USER"]', 'hashedpassword');

-- Insertion dans la table reservation
INSERT INTO reservation (seats, total_price, session_id, user_id, qr_code_url, created_at)
VALUES ('["A1", "A2"]', 25.00, 1, 1, NULL, NOW());

-- Insertion dans la table review
INSERT INTO review (rating, description, validated, user_id, film_id, reservation_id)
VALUES (5, 'Excellent film, à voir absolument !', 1, 1, 1, 1);

-- Insertion dans les tables relationnelles
INSERT INTO film_cinema (film_id, cinema_id) VALUES (1, 1);
INSERT INTO film_genre (film_id, genre_id) VALUES (1, 1);
