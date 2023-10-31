-- Insert user
-- password is 'pass1234'
insert into users (user_id, username, email, password)
values ('83680807-1af5-4a42-9c49-d306193084ec', 'testuser', 'testuser@example.com', '$2y$10$UpH5wezCh0ZozNKvcuz11.9NBaqZ/rMIbBJIW3rGQV/6H5l0m59dO');

-- Insert images
insert into images (filename, image_caption, image_upload_date, image_type, image_size, user_id)
values (
    '00018f8d-f808-4b08-991d-84b83a50e277',
    'This is a test image 1',
    '2023-10-31',
    'image/jpeg',
    12345,
    '83680807-1af5-4a42-9c49-d306193084ec'
);

insert into images (filename, image_caption, image_upload_date, image_type, image_size, user_id)
values (
    '00028f8d-f808-4b08-991d-84b83a50e277',
    'This is a test image 2',
    '2023-10-31',
    'image/png',
    12345,
    '83680807-1af5-4a42-9c49-d306193084ec'
);

insert into images (filename, image_caption, image_upload_date, image_type, image_size, user_id)
values (
    '00038f8d-f808-4b08-991d-84b83a50e277',
    'This is a test image 3',
    '2023-10-31',
    'image/gif',
    12345,
    '83680807-1af5-4a42-9c49-d306193084ec'
);

-- Create an album
insert into albums (album_name, description, user_id)
values (
    'Test Album',
    'This is a test album',
    '83680807-1af5-4a42-9c49-d306193084ec'
);

-- Add images to album
insert into album_images (filename, album_name)
values (
    '00018f8d-f808-4b08-991d-84b83a50e277',
    'Test Album'
);
insert into album_images (filename, album_name)
values (
    '00028f8d-f808-4b08-991d-84b83a50e277',
    'Test Album'
);