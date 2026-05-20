USE library_system;

-- Optional extra data for demonstrations.
INSERT INTO books (category_id, title, author, isbn, publisher, published_year, quantity, available_copies, shelf_location, status)
VALUES
(1, 'Learning PHP, MySQL & JavaScript', 'Robin Nixon', '9781492093824', 'OReilly', 2021, 3, 3, 'CS-A3', 'available'),
(1, 'SQL Antipatterns', 'Bill Karwin', '9781934356555', 'Pragmatic Bookshelf', 2010, 2, 2, 'CS-A4', 'available')
ON DUPLICATE KEY UPDATE title = VALUES(title);

