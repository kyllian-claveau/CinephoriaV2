-- Début de la transaction
START TRANSACTION;

-- Je viens vérifier que les sièges sont disponible avant de procéder à la réservation.
SELECT reserved_seats FROM session WHERE id = 1 FOR UPDATE;

-- J'insère la réseravtion
INSERT INTO reservation (seats, total_price, session_id, user_id, qr_code_url, created_at)
VALUES ('["79", "81"]', 20.00, 1, 1, NULL, NOW());

-- Je récupère l'ID de la réservation
SET @reservation_id = LAST_INSERT_ID();

-- Je met à jour les sièges qui sont réservé dans la table Session
UPDATE session
SET reserved_seats = ('["79", "81"]')
WHERE id = 1;

-- Je valide la transaction
COMMIT;

-- Si jamais je rencontre une erreur, j'annule la transaction avec la commande ci-dessous
-- ROLLBACK;