-- First check if username 'Admin' already exists
SET @admin_username_exists = (SELECT COUNT(*) FROM `admins` WHERE `username` = 'Admin');

-- If username exists, use AdminNew instead
SET @username_to_use = IF(@admin_username_exists > 0, 'AdminNew', 'Admin');

-- Update any existing admin with email 'admin@gmail.com'
UPDATE `admins` SET 
    `email` = 'Admin@gmail.com',
    `password` = '$2y$10$MH58jeWQ3B.qf7D3f0YHk.xPVHFKuGDT2F6frFTX0Hw9kYJu/XDfO', -- Hash for 'adminbloom'
    `updated_at` = NOW()
WHERE `email` = 'admin@gmail.com';

-- Also update any admin with email 'Admin@gmail.com' (in case it exists already)
UPDATE `admins` SET 
    `password` = '$2y$10$MH58jeWQ3B.qf7D3f0YHk.xPVHFKuGDT2F6frFTX0Hw9kYJu/XDfO', -- Hash for 'adminbloom'
    `updated_at` = NOW()
WHERE `email` = 'Admin@gmail.com';

-- If no admin exists, create a new one using unique username
INSERT INTO `admins` (`username`, `email`, `password`, `created_at`, `updated_at`)
SELECT @username_to_use, 'Admin@gmail.com', '$2y$10$MH58jeWQ3B.qf7D3f0YHk.xPVHFKuGDT2F6frFTX0Hw9kYJu/XDfO', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `admins` WHERE `email` = 'Admin@gmail.com' OR `email` = 'admin@gmail.com'
); 