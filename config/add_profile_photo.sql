-- Add profile_photo column to users table if it doesn't exist
ALTER TABLE users
ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT 'assets/img/default-profile.png';

-- Update existing users to have the default profile photo
UPDATE users 
SET profile_photo = 'assets/img/default-profile.png' 
WHERE profile_photo IS NULL; 