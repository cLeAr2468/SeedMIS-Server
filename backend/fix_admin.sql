DELETE FROM admins WHERE email = 'admin@seedmis.com';

INSERT INTO admins (name, email, password, role, created_at, updated_at) 
VALUES (
    'Admin',
    'admin@seedmis.com',
    '$2y$12$LQv3c1yycL6UZP.rELQ8eOr5KdQqL8KJ5X9iVQB.FqZJLQKFRqTYq',
    'admin',
    NOW(),
    NOW()
);
