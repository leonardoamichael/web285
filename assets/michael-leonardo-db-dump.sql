DROP DATABASE IF EXISTS recipe_share;
CREATE DATABASE recipe_share;
USE recipe_share;

CREATE TABLE `active_user_act` (
  `id_usr_act` int(10) UNSIGNED NOT NULL,
  `last_seen_act` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `active_user_act`
--

INSERT INTO `active_user_act` (`id_usr_act`, `last_seen_act`) VALUES
(1, '2026-02-17 08:09:33'),
(2, '2026-05-04 19:06:09'),
(3, '2026-02-21 12:15:12'),
(6, '2026-05-04 13:09:20');

-- --------------------------------------------------------

--
-- Table structure for table `category_cat`
--

CREATE TABLE `category_cat` (
  `id_cat` int(10) UNSIGNED NOT NULL,
  `group_cat` enum('type','style','diet') NOT NULL DEFAULT 'type',
  `name_cat` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category_cat`
--

INSERT INTO `category_cat` (`id_cat`, `group_cat`, `name_cat`) VALUES
(1, 'type', 'Breakfast'),
(5, 'type', 'Dessert'),
(3, 'type', 'Dinner'),
(2, 'type', 'Lunch'),
(23, 'type', 'Side'),
(4, 'type', 'Snack'),
(24, 'style', 'American'),
(21, 'style', 'American Southern'),
(7, 'style', 'Cuban'),
(9, 'style', 'Fusion'),
(26, 'style', 'Irish'),
(6, 'style', 'Italian'),
(29, 'style', 'Korean'),
(25, 'style', 'Lebanese'),
(27, 'style', 'Mexican'),
(10, 'style', 'N/A'),
(28, 'style', 'Pasta'),
(8, 'style', 'Thai'),
(15, 'diet', 'Dairy-free'),
(13, 'diet', 'Gluten-free'),
(16, 'diet', 'Keto'),
(20, 'diet', 'N/A'),
(17, 'diet', 'Paleo'),
(19, 'diet', 'Raw'),
(14, 'diet', 'Sugar-free'),
(11, 'diet', 'Vegan'),
(12, 'diet', 'Vegetarian'),
(18, 'diet', 'Whole food');

-- --------------------------------------------------------

--
-- Table structure for table `ingredient_ing`
--

CREATE TABLE `ingredient_ing` (
  `id_ing` int(10) UNSIGNED NOT NULL,
  `name_ing` varchar(120) NOT NULL,
  `kcal_per_100g_ing` decimal(6,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredient_ing`
--

INSERT INTO `ingredient_ing` (`id_ing`, `name_ing`, `kcal_per_100g_ing`) VALUES
(1, 'spaghetti', NULL),
(2, 'olive oil', NULL),
(3, 'garlic', NULL),
(4, 'crushed tomatoes', NULL),
(5, 'salt', NULL),
(6, 'black pepper', NULL),
(7, 'bread', NULL),
(8, 'peanut butter', NULL),
(9, 'jelly', NULL),
(10, 'pasta', NULL),
(11, 'mushrooms', NULL),
(12, 'butter', NULL),
(13, 'parmesan', NULL),
(14, 'onion', NULL),
(15, 'red onion', NULL),
(16, 'yellow onion', NULL),
(17, 'green onion', NULL),
(18, 'shallot', NULL),
(19, 'carrot', NULL),
(20, 'celery', NULL),
(21, 'bell pepper', NULL),
(22, 'red bell pepper', NULL),
(23, 'green bell pepper', NULL),
(24, 'jalapeno', NULL),
(25, 'broccoli', NULL),
(26, 'cauliflower', NULL),
(27, 'zucchini', NULL),
(28, 'cucumber', NULL),
(29, 'spinach', NULL),
(30, 'kale', NULL),
(31, 'lettuce', NULL),
(32, 'romaine', NULL),
(33, 'arugula', NULL),
(34, 'cabbage', NULL),
(35, 'red cabbage', NULL),
(36, 'potato', NULL),
(37, 'sweet potato', NULL),
(38, 'corn', NULL),
(39, 'peas', NULL),
(40, 'green beans', NULL),
(41, 'asparagus', NULL),
(42, 'mushroom', NULL),
(43, 'portobello mushroom', NULL),
(44, 'apple', NULL),
(45, 'banana', NULL),
(46, 'strawberry', NULL),
(47, 'blueberry', NULL),
(48, 'raspberry', NULL),
(49, 'lemon', NULL),
(50, 'lime', NULL),
(51, 'orange', NULL),
(52, 'pineapple', NULL),
(53, 'mango', NULL),
(54, 'avocado', NULL),
(55, 'chicken', NULL),
(56, 'chicken breast', NULL),
(57, 'chicken thigh', NULL),
(58, 'ground beef', NULL),
(59, 'beef', NULL),
(60, 'steak', NULL),
(61, 'pork', NULL),
(62, 'bacon', NULL),
(63, 'sausage', NULL),
(64, 'egg', NULL),
(65, 'tofu', NULL),
(66, 'black beans', NULL),
(67, 'kidney beans', NULL),
(68, 'chickpeas', NULL),
(69, 'lentils', NULL),
(70, 'milk', NULL),
(71, 'cream', NULL),
(72, 'heavy cream', NULL),
(73, 'yogurt', NULL),
(74, 'cheddar cheese', NULL),
(75, 'mozzarella', NULL),
(76, 'cream cheese', NULL),
(77, 'rice', NULL),
(78, 'brown rice', NULL),
(79, 'white rice', NULL),
(80, 'flour', NULL),
(81, 'all purpose flour', NULL),
(82, 'sugar', NULL),
(83, 'brown sugar', NULL),
(84, 'oats', NULL),
(85, 'quinoa', NULL),
(86, 'vegetable oil', NULL),
(87, 'canola oil', NULL),
(88, 'sesame oil', NULL),
(89, 'vinegar', NULL),
(90, 'balsamic vinegar', NULL),
(91, 'soy sauce', NULL),
(92, 'honey', NULL),
(93, 'maple syrup', NULL),
(94, 'basil', NULL),
(95, 'parsley', NULL),
(96, 'cilantro', NULL),
(97, 'thyme', NULL),
(98, 'rosemary', NULL),
(99, 'oregano', NULL),
(100, 'mint', NULL),
(101, 'paprika', NULL),
(102, 'smoked paprika', NULL),
(103, 'cumin', NULL),
(104, 'chili powder', NULL),
(105, 'garlic powder', NULL),
(106, 'onion powder', NULL),
(107, 'italian seasoning', NULL),
(108, 'cinnamon', NULL),
(109, 'nutmeg', NULL),
(110, 'ginger', NULL),
(111, 'turmeric', NULL),
(112, 'red pepper flakes', NULL),
(113, 'baking powder', NULL),
(114, 'baking soda', NULL),
(115, 'vanilla extract', NULL),
(116, 'chocolate', NULL),
(117, 'cocoa powder', NULL),
(118, 'cornstarch', NULL),
(119, 'yeast', NULL),
(120, 'powdered sugar', NULL),
(121, 'cereal', NULL),
(122, 'white bread', NULL),
(123, 'baked beans', NULL),
(124, 'beans', NULL),
(125, 'cornmeal', NULL),
(126, 'all-purpose flour', NULL),
(127, 'tomato', NULL),
(128, 'lemon juice', NULL),
(129, 'potatoes', NULL),
(130, 'canned tomatoes', NULL),
(131, 'vegetable broth', NULL),
(132, 'olive oils', NULL),
(133, 'pepper', NULL),
(134, 'chicken breasts', NULL),
(135, 'plain yogurt', NULL),
(136, 'mixed berries', NULL),
(137, 'granola', NULL),
(138, 'nothing', NULL),
(139, 'cherry', NULL),
(140, 'water', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `level_lev`
--

CREATE TABLE `level_lev` (
  `id_lev` int(10) UNSIGNED NOT NULL,
  `level_number_lev` tinyint(3) UNSIGNED NOT NULL,
  `name_lev` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `level_lev`
--

INSERT INTO `level_lev` (`id_lev`, `level_number_lev`, `name_lev`) VALUES
(1, 1, 'Level 1'),
(2, 2, 'Level 2'),
(3, 3, 'Level 3');

-- --------------------------------------------------------

--
-- Table structure for table `recipe_category_reccat`
--

CREATE TABLE `recipe_category_reccat` (
  `id_reccat` int(10) UNSIGNED NOT NULL,
  `id_rec_reccat` int(10) UNSIGNED NOT NULL,
  `id_cat_reccat` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipe_category_reccat`
--

INSERT INTO `recipe_category_reccat` (`id_reccat`, `id_rec_reccat`, `id_cat_reccat`) VALUES
(32, 15, 12),
(31, 15, 21),
(30, 15, 23),
(33, 16, 1),
(36, 16, 2),
(35, 16, 3),
(38, 16, 4),
(34, 16, 5),
(40, 16, 12),
(39, 16, 21),
(37, 16, 23),
(41, 17, 3),
(43, 17, 6),
(42, 17, 9),
(44, 17, 12),
(46, 18, 2),
(45, 18, 3),
(48, 18, 4),
(52, 18, 6),
(50, 18, 7),
(53, 18, 8),
(51, 18, 9),
(56, 18, 11),
(57, 18, 12),
(54, 18, 15),
(58, 18, 18),
(55, 18, 19),
(49, 18, 21),
(47, 18, 23),
(75, 20, 3),
(78, 20, 11),
(79, 20, 12),
(77, 20, 15),
(80, 20, 18),
(76, 20, 21),
(71, 21, 2),
(70, 21, 3),
(72, 21, 4),
(74, 21, 12),
(73, 21, 21),
(93, 22, 2),
(92, 22, 3),
(98, 22, 6),
(96, 22, 7),
(97, 22, 9),
(100, 22, 11),
(101, 22, 12),
(99, 22, 15),
(102, 22, 18),
(95, 22, 21),
(94, 22, 23),
(103, 23, 1),
(104, 23, 2),
(107, 23, 11),
(108, 23, 12),
(106, 23, 15),
(105, 23, 21),
(109, 24, 3),
(112, 24, 13),
(111, 24, 15),
(113, 24, 17),
(110, 24, 21),
(114, 25, 1),
(116, 25, 2),
(117, 25, 4),
(115, 25, 5),
(120, 25, 12),
(119, 25, 13),
(118, 25, 21),
(122, 26, 3),
(121, 26, 5),
(125, 26, 15),
(126, 26, 18),
(123, 26, 21),
(124, 26, 28),
(127, 27, 1),
(128, 27, 10),
(129, 27, 15);

-- --------------------------------------------------------

--
-- Table structure for table `recipe_image_recimg`
--

CREATE TABLE `recipe_image_recimg` (
  `id_recimg` int(10) UNSIGNED NOT NULL,
  `id_rec_recimg` int(10) UNSIGNED NOT NULL,
  `path_recimg` varchar(255) NOT NULL,
  `alt_recimg` varchar(120) DEFAULT NULL,
  `sort_order_recimg` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `created_at_recimg` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipe_image_recimg`
--

INSERT INTO `recipe_image_recimg` (`id_recimg`, `id_rec_recimg`, `path_recimg`, `alt_recimg`, `sort_order_recimg`, `created_at_recimg`) VALUES
(6, 15, 'uploads/recipes/rec_15_d66625ba2dd1641f.png', 'Classic Skillet Cornbread', 1, '2026-03-09 04:27:02'),
(7, 16, 'uploads/recipes/rec_16_e3730e61de0b7ed0.png', 'Classic Pancakes', 1, '2026-03-09 04:37:42'),
(8, 17, 'uploads/recipes/rec_17_1dc5ea1906d64612.png', 'Garlic Butter Pasta', 1, '2026-03-09 04:45:37'),
(9, 18, 'uploads/recipes/rec_18_a839a7eb89187670.png', 'Simple Garden Salad', 1, '2026-03-12 15:51:54'),
(10, 20, 'uploads/recipes/rec_20_ba1c6d70f5cda563.png', 'Oven Roasted Potatoes', 1, '2026-03-13 04:19:53'),
(11, 21, 'uploads/recipes/rec_21_a4e9ca16ecf8b697.png', 'Grilled Cheese Sandwich', 1, '2026-03-13 04:36:35'),
(12, 22, 'uploads/recipes/rec_22_3580ffe915678912.png', 'Simple Tomato Soup', 1, '2026-03-13 08:47:03'),
(13, 23, 'uploads/recipes/rec_23_bf61e2f036f14bd0.png', 'Peanut Butter Banana Toast', 1, '2026-03-13 08:56:22'),
(14, 24, 'uploads/recipes/rec_24_9a0d92575f066a6d.png', 'Baked Chicken Breast', 1, '2026-03-13 09:01:09'),
(15, 25, 'uploads/recipes/rec_25_62022032131c1fc8.png', 'Fruit Yogurt Bowl', 1, '2026-03-13 09:04:31'),
(16, 27, 'uploads/recipes/rec_27_701900a16789b086.jpg', 'cherry coke', 1, '2026-05-04 18:27:21');

-- --------------------------------------------------------

--
-- Table structure for table `recipe_ingredient_recing`
--

CREATE TABLE `recipe_ingredient_recing` (
  `id_recing` int(10) UNSIGNED NOT NULL,
  `id_rec_recing` int(10) UNSIGNED NOT NULL,
  `id_ing_recing` int(10) UNSIGNED NOT NULL,
  `quantity_recing` decimal(8,2) DEFAULT NULL,
  `id_uni_recing` int(10) UNSIGNED DEFAULT NULL,
  `note_recing` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipe_ingredient_recing`
--

INSERT INTO `recipe_ingredient_recing` (`id_recing`, `id_rec_recing`, `id_ing_recing`, `quantity_recing`, `id_uni_recing`, `note_recing`) VALUES
(27, 15, 125, 1.00, 1, NULL),
(28, 15, 126, 1.00, 1, NULL),
(29, 15, 82, 1.00, 2, NULL),
(30, 15, 113, 1.00, 2, NULL),
(31, 15, 5, 0.50, 3, NULL),
(32, 15, 70, 1.00, 1, NULL),
(33, 15, 64, 1.00, NULL, NULL),
(34, 15, 12, 0.25, 1, 'Melted'),
(35, 15, 86, 1.00, 2, NULL),
(36, 16, 126, 1.00, 1, NULL),
(37, 16, 82, 1.00, 2, NULL),
(38, 16, 113, 1.00, 3, NULL),
(39, 16, 5, 0.25, 3, NULL),
(40, 16, 70, 1.00, 1, NULL),
(41, 16, 64, 1.00, NULL, NULL),
(42, 16, 12, 2.00, 2, 'Melted'),
(43, 17, 1, 16.00, 5, NULL),
(44, 17, 12, 2.00, 2, NULL),
(45, 17, 3, 1.00, 2, 'Minced'),
(46, 17, 5, 0.25, 3, NULL),
(47, 17, 6, 0.25, 3, NULL),
(48, 17, 13, 2.00, 2, 'Grated'),
(49, 18, 31, 2.00, 1, NULL),
(50, 18, 127, 1.00, 1, 'chopped'),
(51, 18, 28, 0.50, 1, 'sliced'),
(52, 18, 2, 2.00, 2, NULL),
(53, 18, 128, 1.00, 2, NULL),
(60, 21, 7, 2.00, 13, NULL),
(61, 21, 74, 2.00, 13, NULL),
(62, 21, 12, 1.00, 2, NULL),
(63, 20, 129, 3.00, 1, 'diced'),
(64, 20, 2, 2.00, 2, NULL),
(65, 20, 5, 1.00, 3, NULL),
(66, 20, 6, 0.25, 3, NULL),
(67, 20, 105, 1.00, 3, NULL),
(73, 22, 130, 2.00, 1, 'crushed'),
(74, 22, 131, 1.00, 1, NULL),
(75, 22, 2, 1.00, 2, NULL),
(76, 22, 5, 0.50, 3, NULL),
(77, 22, 133, 0.25, 3, NULL),
(78, 23, 7, 1.00, 13, NULL),
(79, 23, 8, 1.00, 2, NULL),
(80, 23, 45, 0.50, 11, NULL),
(81, 24, 134, 2.00, 11, NULL),
(82, 24, 2, 1.00, 2, NULL),
(83, 24, 5, 1.00, 3, NULL),
(84, 24, 133, 0.50, 3, NULL),
(85, 24, 101, 0.50, 3, NULL),
(86, 25, 135, 1.00, 1, NULL),
(87, 25, 136, 0.50, 1, NULL),
(88, 25, 92, 1.00, 2, NULL),
(89, 25, 137, 1.00, 2, NULL),
(90, 26, 138, 67.00, 2, NULL),
(91, 27, 139, 1.00, 11, NULL),
(92, 27, 140, 1.00, 16, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recipe_rating_rtg`
--

CREATE TABLE `recipe_rating_rtg` (
  `id_rtg` int(10) UNSIGNED NOT NULL,
  `id_rec_rtg` int(10) UNSIGNED NOT NULL,
  `id_usr_rtg` int(10) UNSIGNED NOT NULL,
  `rating_rtg` tinyint(3) UNSIGNED NOT NULL,
  `created_at_rtg` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipe_rating_rtg`
--

INSERT INTO `recipe_rating_rtg` (`id_rtg`, `id_rec_rtg`, `id_usr_rtg`, `rating_rtg`, `created_at_rtg`) VALUES
(14, 15, 2, 5, '2026-03-09 04:27:33'),
(15, 25, 2, 5, '2026-03-15 23:32:12'),
(16, 20, 2, 3, '2026-03-15 23:39:29'),
(17, 23, 2, 5, '2026-03-16 03:00:15'),
(18, 24, 2, 3, '2026-05-02 14:30:24'),
(19, 24, 5, 4, '2026-05-02 14:31:32');

-- --------------------------------------------------------

--
-- Table structure for table `recipe_rec`
--

CREATE TABLE `recipe_rec` (
  `id_rec` int(10) UNSIGNED NOT NULL,
  `id_usr_rec` int(10) UNSIGNED NOT NULL,
  `title_rec` varchar(120) NOT NULL,
  `description_rec` varchar(255) NOT NULL DEFAULT '',
  `prep_minutes_rec` int(10) UNSIGNED DEFAULT NULL,
  `cook_minutes_rec` int(10) UNSIGNED DEFAULT NULL,
  `youtube_url_rec` varchar(255) DEFAULT NULL,
  `created_at_rec` datetime NOT NULL DEFAULT current_timestamp(),
  `status_rec` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by_usr` int(11) DEFAULT NULL,
  `reviewed_at_rec` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipe_rec`
--

INSERT INTO `recipe_rec` (`id_rec`, `id_usr_rec`, `title_rec`, `description_rec`, `prep_minutes_rec`, `cook_minutes_rec`, `youtube_url_rec`, `created_at_rec`, `status_rec`, `reviewed_by_usr`, `reviewed_at_rec`) VALUES
(15, 2, 'Classic Skillet Cornbread', '', 10, 20, NULL, '2026-03-09 04:27:02', 'approved', 2, '2026-03-09 04:27:16'),
(16, 2, 'Classic Pancakes', '', NULL, NULL, NULL, '2026-03-09 04:37:42', 'approved', 2, '2026-03-09 04:37:49'),
(17, 2, 'Garlic Butter Pasta', '', 5, 15, NULL, '2026-03-09 04:45:37', 'approved', 2, '2026-03-09 04:45:44'),
(18, 2, 'Simple Garden Salad', '', 10, NULL, 'https://youtu.be/dQw4w9WgXcQ?si=hz7XtUHiNV4bF8ut', '2026-03-12 15:51:54', 'approved', 2, '2026-03-12 17:17:55'),
(20, 2, 'Oven Roasted Potatoes', 'Potatoes roasted in the oven', 10, 30, NULL, '2026-03-13 04:19:53', 'approved', 2, '2026-03-13 04:20:08'),
(21, 2, 'Grilled Cheese Sandwich', 'Buttery toasted bread with melted cheese on the inside.', 5, 5, NULL, '2026-03-13 04:36:35', 'approved', 2, '2026-03-13 04:36:44'),
(22, 5, 'Simple Tomato Soup', 'This is your basic tomato soup.', 5, 20, 'https://youtu.be/dQw4w9WgXcQ?si=zsernYJ5u37DUIzs', '2026-03-13 08:47:03', 'approved', 2, '2026-03-13 09:05:05'),
(23, 5, 'Peanut Butter Banana Toast', 'Peanut butter toast, with bananas.', 5, 2, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '2026-03-13 08:56:22', 'approved', 2, '2026-03-13 09:05:04'),
(24, 5, 'Baked Chicken Breast', 'Roasted chicken with seasoning', 5, 25, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '2026-03-13 09:01:09', 'approved', 2, '2026-03-13 09:05:02'),
(25, 5, 'Fruit Yogurt Bowl', 'A bowl of yogurt with fruit.', 5, NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '2026-03-13 09:04:31', 'approved', 2, '2026-03-13 09:04:52'),
(26, 2, 'Nothing Burger', 'Nothing', 1091, 1097, NULL, '2026-05-04 16:36:11', 'approved', 2, '2026-05-04 16:36:22'),
(27, 2, 'cherry coke', '', 840, 1020, NULL, '2026-05-04 18:27:21', 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recipe_step_stp`
--

CREATE TABLE `recipe_step_stp` (
  `id_stp` int(10) UNSIGNED NOT NULL,
  `id_rec_stp` int(10) UNSIGNED NOT NULL,
  `step_number_stp` smallint(5) UNSIGNED NOT NULL,
  `instruction_stp` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipe_step_stp`
--

INSERT INTO `recipe_step_stp` (`id_stp`, `id_rec_stp`, `step_number_stp`, `instruction_stp`) VALUES
(30, 15, 1, 'Preheat oven to 400°F (200°C). Place an oven-safe skillet in the oven while it heats.'),
(31, 15, 2, 'In a bowl, mix cornmeal, flour, sugar, baking powder, and salt.'),
(32, 15, 3, 'In another bowl, whisk milk, egg, and melted butter.'),
(33, 15, 4, 'Pour the wet ingredients into the dry ingredients and stir until just combined.'),
(34, 15, 5, 'Carefully remove the hot skillet, add the vegetable oil, and swirl to coat the pan.'),
(35, 15, 6, 'Pour the batter into the hot skillet.'),
(36, 15, 7, 'Bake for 18–20 minutes, until the top is golden and a toothpick inserted in the center comes out clean.'),
(37, 15, 8, 'Let cool slightly before slicing and serving.'),
(38, 16, 1, 'Mix flour, sugar, baking powder, and salt in a bowl.'),
(39, 16, 2, 'In another bowl whisk milk, egg, and melted butter.'),
(40, 16, 3, 'Combine wet and dry ingredients until smooth.'),
(41, 16, 4, 'Heat a lightly oiled skillet over medium heat.'),
(42, 16, 5, 'Pour batter and cook until bubbles form.'),
(43, 16, 6, 'Flip and cook until golden brown.'),
(44, 17, 1, 'Cook spaghetti according to package instructions.'),
(45, 17, 2, 'Melt butter in a pan over medium heat.'),
(46, 17, 3, 'Add garlic and cook for 1 minute.'),
(47, 17, 4, 'Drain pasta and add to the pan.'),
(48, 17, 5, 'Toss with salt, pepper, and parmesan.'),
(49, 17, 6, 'Serve warm.'),
(50, 18, 1, 'Combine lettuce, tomato, and cucumber in a bowl.'),
(51, 18, 2, 'Mix olive oil, lemon juice, and salt in a small cup.'),
(52, 18, 3, 'Pour dressing over salad.'),
(53, 18, 4, 'Toss gently and serve.'),
(60, 21, 1, 'Butter one side of each bread slice.'),
(61, 21, 2, 'Place cheese between slices with butter facing out.'),
(62, 21, 3, 'Cook in skillet over medium heat.'),
(63, 21, 4, 'Flip when golden brown.'),
(64, 21, 5, 'Cook other side until cheese melts.'),
(65, 20, 1, 'Preheat oven to 425°F (220°C).'),
(66, 20, 2, 'Toss potatoes with olive oil and seasonings.'),
(67, 20, 3, 'Spread on baking sheet.'),
(68, 20, 4, 'Roast for 25–30 minutes until crispy.'),
(74, 22, 1, 'Heat olive oil in a saucepan.'),
(75, 22, 2, 'Add tomatoes and broth.'),
(76, 22, 3, 'Simmer for 15 minutes.'),
(77, 22, 4, 'Season with salt and pepper.'),
(78, 22, 5, 'Blend if smoother texture is desired.'),
(79, 23, 1, 'Toast the bread.'),
(80, 23, 2, 'Spread peanut butter evenly.'),
(81, 23, 3, 'Top with banana slices.'),
(82, 23, 4, 'Serve immediately.'),
(83, 24, 1, 'Preheat oven to 375°F (190°C).'),
(84, 24, 2, 'Rub chicken with olive oil and spices.'),
(85, 24, 3, 'Place on baking sheet.'),
(86, 24, 4, 'Bake 22–25 minutes until cooked through.'),
(87, 25, 1, 'Add yogurt to a bowl.'),
(88, 25, 2, 'Top with berries and granola.'),
(89, 25, 3, 'Drizzle honey on top.'),
(90, 25, 4, 'Serve chilled.'),
(91, 26, 1, 'Do nothing'),
(92, 26, 2, 'Serve chilled'),
(93, 27, 1, 'add cherry to water'),
(94, 27, 2, 'shake it all up');

-- --------------------------------------------------------

--
-- Table structure for table `role_rol`
--

CREATE TABLE `role_rol` (
  `id_rol` int(10) UNSIGNED NOT NULL,
  `name_rol` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_rol`
--

INSERT INTO `role_rol` (`id_rol`, `name_rol`) VALUES
(1, 'admin'),
(2, 'member'),
(3, 'super_admin');

-- --------------------------------------------------------

--
-- Table structure for table `unit_uni`
--

CREATE TABLE `unit_uni` (
  `id_uni` int(10) UNSIGNED NOT NULL,
  `name_uni` varchar(30) NOT NULL,
  `abbreviation_uni` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unit_uni`
--

INSERT INTO `unit_uni` (`id_uni`, `name_uni`, `abbreviation_uni`) VALUES
(1, 'cup', 'c'),
(2, 'tablespoon', 'tbsp'),
(3, 'teaspoon', 'tsp'),
(4, 'pound', 'lb'),
(5, 'ounce', 'oz'),
(6, 'gram', 'g'),
(7, 'kilogram', 'kg'),
(8, 'milliliter', 'ml'),
(9, 'liter', 'l'),
(10, 'piece', 'pc'),
(11, 'whole', 'whole'),
(12, 'clove', 'clove'),
(13, 'slice', 'slice'),
(14, 'stick', 'stick'),
(15, 'can', 'can'),
(16, 'jar', 'jar'),
(17, 'package', 'pkg'),
(18, 'bunch', 'bunch');

-- --------------------------------------------------------

--
-- Table structure for table `usability_test_response_usr`
--

CREATE TABLE `usability_test_response_usr` (
  `id_usrtest` int(10) UNSIGNED NOT NULL,
  `tester_name_usrtest` varchar(100) DEFAULT NULL,
  `age_range_usrtest` varchar(50) DEFAULT NULL,
  `impression_design_usrtest` text DEFAULT NULL,
  `impression_purpose_usrtest` text DEFAULT NULL,
  `impression_trust_usrtest` text DEFAULT NULL,
  `impression_readability_usrtest` text DEFAULT NULL,
  `task1_success_usrtest` varchar(10) DEFAULT NULL,
  `task1_comments_usrtest` text DEFAULT NULL,
  `task2_success_usrtest` varchar(10) DEFAULT NULL,
  `task2_comments_usrtest` text DEFAULT NULL,
  `task3_success_usrtest` varchar(10) DEFAULT NULL,
  `task3_comments_usrtest` text DEFAULT NULL,
  `task4_success_usrtest` varchar(10) DEFAULT NULL,
  `task4_comments_usrtest` text DEFAULT NULL,
  `created_at_usrtest` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_usr`
--

CREATE TABLE `user_usr` (
  `id_usr` int(10) UNSIGNED NOT NULL,
  `username_usr` varchar(50) NOT NULL,
  `email_usr` varchar(255) NOT NULL,
  `password_hash_usr` varchar(255) NOT NULL,
  `id_rol_usr` int(10) UNSIGNED NOT NULL,
  `admin_active_usr` tinyint(1) NOT NULL DEFAULT 1,
  `id_lev_usr` int(10) UNSIGNED NOT NULL,
  `created_at_usr` datetime NOT NULL DEFAULT current_timestamp(),
  `status_usr` enum('active','disabled') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_usr`
--

INSERT INTO `user_usr` (`id_usr`, `username_usr`, `email_usr`, `password_hash_usr`, `id_rol_usr`, `admin_active_usr`, `id_lev_usr`, `created_at_usr`, `status_usr`) VALUES
(1, 'testuser', 'test@test.com', '$2y$10$8Z7eLZIjktnr9pMc/LFTyeRwBpSFeUNlN3ARMW1Qj3dABGkKUjZne', 2, 1, 1, '2026-02-17 08:08:53', 'active'),
(2, 'admin', 'test123@test.com', '$2y$10$X0A90VNjC4IYIG1niZPRkep85ERHcMXTmE9mFxy7x2kjDpOh53PVK', 3, 1, 1, '2026-02-19 11:37:25', 'active'),
(3, '1user', '11111@test.com', '$2y$10$XtrsqB0UZSn/C/j.kgkZie8PD5tiZ8tazig4tzstKU1S9o5eXL7iW', 2, 1, 1, '2026-02-21 10:31:42', 'active'),
(4, 'recipeadmin', 'recipeadmin@test.com', '$2y$10$O7lUQu4uz2w5AV3z4On2d..HuuQN9hrFFdeSUU1TrID2k61e1tMc.', 2, 1, 1, '2026-03-06 15:35:02', 'active'),
(5, 'Cookingfoods', 'test1234@test.com', '$2y$10$iXD5hrEwCXuNwjGmY0BPC.02P0dTHm94P/cT5GAfOlEzX8Ftps9w2', 1, 1, 1, '2026-03-13 08:41:58', 'active'),
(6, 'jrjr', 'jr@test.com', '$2y$10$JbUkYnMSuYIi.mraf1Iiue4Ytd5CHa6VHbyBx.YTA0Z7qZ3zVVHRO', 2, 1, 1, '2026-05-04 13:08:30', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_user_act`
--
ALTER TABLE `active_user_act`
  ADD PRIMARY KEY (`id_usr_act`);

--
-- Indexes for table `category_cat`
--
ALTER TABLE `category_cat`
  ADD PRIMARY KEY (`id_cat`),
  ADD UNIQUE KEY `uq_group_name` (`group_cat`,`name_cat`);

--
-- Indexes for table `ingredient_ing`
--
ALTER TABLE `ingredient_ing`
  ADD PRIMARY KEY (`id_ing`),
  ADD UNIQUE KEY `name_ing` (`name_ing`);

--
-- Indexes for table `level_lev`
--
ALTER TABLE `level_lev`
  ADD PRIMARY KEY (`id_lev`),
  ADD UNIQUE KEY `level_number_lev` (`level_number_lev`),
  ADD UNIQUE KEY `name_lev` (`name_lev`);

--
-- Indexes for table `recipe_category_reccat`
--
ALTER TABLE `recipe_category_reccat`
  ADD PRIMARY KEY (`id_reccat`),
  ADD UNIQUE KEY `uq_rec_cat` (`id_rec_reccat`,`id_cat_reccat`),
  ADD KEY `fk_reccat_category` (`id_cat_reccat`);

--
-- Indexes for table `recipe_image_recimg`
--
ALTER TABLE `recipe_image_recimg`
  ADD PRIMARY KEY (`id_recimg`),
  ADD KEY `idx_recimg_recipe` (`id_rec_recimg`);

--
-- Indexes for table `recipe_ingredient_recing`
--
ALTER TABLE `recipe_ingredient_recing`
  ADD PRIMARY KEY (`id_recing`),
  ADD UNIQUE KEY `uq_rec_ing` (`id_rec_recing`,`id_ing_recing`),
  ADD KEY `fk_recing_ing` (`id_ing_recing`),
  ADD KEY `fk_recing_unit` (`id_uni_recing`);

--
-- Indexes for table `recipe_rating_rtg`
--
ALTER TABLE `recipe_rating_rtg`
  ADD PRIMARY KEY (`id_rtg`),
  ADD UNIQUE KEY `uq_recipe_user` (`id_rec_rtg`,`id_usr_rtg`),
  ADD KEY `fk_rtg_user` (`id_usr_rtg`);

--
-- Indexes for table `recipe_rec`
--
ALTER TABLE `recipe_rec`
  ADD PRIMARY KEY (`id_rec`),
  ADD KEY `fk_recipe_user` (`id_usr_rec`);

--
-- Indexes for table `recipe_step_stp`
--
ALTER TABLE `recipe_step_stp`
  ADD PRIMARY KEY (`id_stp`),
  ADD UNIQUE KEY `uq_rec_step` (`id_rec_stp`,`step_number_stp`);

--
-- Indexes for table `role_rol`
--
ALTER TABLE `role_rol`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `name_rol` (`name_rol`);

--
-- Indexes for table `unit_uni`
--
ALTER TABLE `unit_uni`
  ADD PRIMARY KEY (`id_uni`),
  ADD UNIQUE KEY `name_uni` (`name_uni`),
  ADD UNIQUE KEY `abbreviation_uni` (`abbreviation_uni`);

--
-- Indexes for table `usability_test_response_usr`
--
ALTER TABLE `usability_test_response_usr`
  ADD PRIMARY KEY (`id_usrtest`);

--
-- Indexes for table `user_usr`
--
ALTER TABLE `user_usr`
  ADD PRIMARY KEY (`id_usr`),
  ADD UNIQUE KEY `username_usr` (`username_usr`),
  ADD UNIQUE KEY `email_usr` (`email_usr`),
  ADD KEY `fk_user_role` (`id_rol_usr`),
  ADD KEY `fk_user_level` (`id_lev_usr`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category_cat`
--
ALTER TABLE `category_cat`
  MODIFY `id_cat` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `ingredient_ing`
--
ALTER TABLE `ingredient_ing`
  MODIFY `id_ing` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `level_lev`
--
ALTER TABLE `level_lev`
  MODIFY `id_lev` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `recipe_category_reccat`
--
ALTER TABLE `recipe_category_reccat`
  MODIFY `id_reccat` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `recipe_image_recimg`
--
ALTER TABLE `recipe_image_recimg`
  MODIFY `id_recimg` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `recipe_ingredient_recing`
--
ALTER TABLE `recipe_ingredient_recing`
  MODIFY `id_recing` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `recipe_rating_rtg`
--
ALTER TABLE `recipe_rating_rtg`
  MODIFY `id_rtg` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `recipe_rec`
--
ALTER TABLE `recipe_rec`
  MODIFY `id_rec` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `recipe_step_stp`
--
ALTER TABLE `recipe_step_stp`
  MODIFY `id_stp` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `role_rol`
--
ALTER TABLE `role_rol`
  MODIFY `id_rol` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `unit_uni`
--
ALTER TABLE `unit_uni`
  MODIFY `id_uni` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `usability_test_response_usr`
--
ALTER TABLE `usability_test_response_usr`
  MODIFY `id_usrtest` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_usr`
--
ALTER TABLE `user_usr`
  MODIFY `id_usr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `active_user_act`
--
ALTER TABLE `active_user_act`
  ADD CONSTRAINT `fk_act_user` FOREIGN KEY (`id_usr_act`) REFERENCES `user_usr` (`id_usr`) ON DELETE CASCADE;

--
-- Constraints for table `recipe_category_reccat`
--
ALTER TABLE `recipe_category_reccat`
  ADD CONSTRAINT `fk_reccat_category` FOREIGN KEY (`id_cat_reccat`) REFERENCES `category_cat` (`id_cat`),
  ADD CONSTRAINT `fk_reccat_recipe` FOREIGN KEY (`id_rec_reccat`) REFERENCES `recipe_rec` (`id_rec`);

--
-- Constraints for table `recipe_image_recimg`
--
ALTER TABLE `recipe_image_recimg`
  ADD CONSTRAINT `fk_recimg_recipe` FOREIGN KEY (`id_rec_recimg`) REFERENCES `recipe_rec` (`id_rec`) ON DELETE CASCADE;

--
-- Constraints for table `recipe_ingredient_recing`
--
ALTER TABLE `recipe_ingredient_recing`
  ADD CONSTRAINT `fk_recing_ing` FOREIGN KEY (`id_ing_recing`) REFERENCES `ingredient_ing` (`id_ing`),
  ADD CONSTRAINT `fk_recing_recipe` FOREIGN KEY (`id_rec_recing`) REFERENCES `recipe_rec` (`id_rec`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recing_unit` FOREIGN KEY (`id_uni_recing`) REFERENCES `unit_uni` (`id_uni`) ON DELETE SET NULL;

--
-- Constraints for table `recipe_rating_rtg`
--
ALTER TABLE `recipe_rating_rtg`
  ADD CONSTRAINT `fk_rtg_recipe` FOREIGN KEY (`id_rec_rtg`) REFERENCES `recipe_rec` (`id_rec`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rtg_user` FOREIGN KEY (`id_usr_rtg`) REFERENCES `user_usr` (`id_usr`) ON DELETE CASCADE;

--
-- Constraints for table `recipe_rec`
--
ALTER TABLE `recipe_rec`
  ADD CONSTRAINT `fk_recipe_user` FOREIGN KEY (`id_usr_rec`) REFERENCES `user_usr` (`id_usr`);

--
-- Constraints for table `recipe_step_stp`
--
ALTER TABLE `recipe_step_stp`
  ADD CONSTRAINT `fk_stp_recipe` FOREIGN KEY (`id_rec_stp`) REFERENCES `recipe_rec` (`id_rec`) ON DELETE CASCADE;

--
-- Constraints for table `user_usr`
--
ALTER TABLE `user_usr`
  ADD CONSTRAINT `fk_user_level` FOREIGN KEY (`id_lev_usr`) REFERENCES `level_lev` (`id_lev`),
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`id_rol_usr`) REFERENCES `role_rol` (`id_rol`);
COMMIT;

