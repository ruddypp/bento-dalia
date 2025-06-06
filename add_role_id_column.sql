-- Add role_id column to users table if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS role_id INT DEFAULT NULL AFTER password;

-- Add foreign key constraint to link users.role_id to roles.id_role
ALTER TABLE users
ADD CONSTRAINT fk_user_role
FOREIGN KEY (role_id) REFERENCES roles(id_role)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- Update user roles based on username
UPDATE users SET role_id = 1 WHERE username = 'admin'; -- Admin role
UPDATE users SET role_id = 3 WHERE username = 'kasir'; -- Kasir role
UPDATE users SET role_id = 4 WHERE username = 'headproduksi'; -- Head Produksi role
UPDATE users SET role_id = 2 WHERE username = 'purchasing'; -- Purchasing role
UPDATE users SET role_id = 5 WHERE username = 'crew'; -- Crew role 