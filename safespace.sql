-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2026 at 11:19 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `safespace`
--

-- --------------------------------------------------------

--
-- Table structure for table `app_ratings`
--

CREATE TABLE `app_ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_ratings`
--

INSERT INTO `app_ratings` (`id`, `user_id`, `rating`, `created_at`) VALUES
(1, 3, 5, '2026-05-30 21:06:12'),
(5, 2, 4, '2026-05-30 18:10:35');

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `likes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `creator_id`, `title`, `content`, `likes`, `created_at`) VALUES
(2, 3, 'azerty', 'azertyuiop', 2, '2026-04-20 15:28:25'),
(3, 3, 'azerty', 'azertyuiop', 3, '2026-05-30 15:28:25'),
(4, 3, 'azerty', 'azertyuiop', 4, '2026-05-30 15:28:25'),
(5, 3, 'azerty', 'azertyuiop', 5, '2026-05-30 15:28:25'),
(6, 3, 'azerty', 'azertyuiop', 6, '2026-05-30 15:28:25'),
(7, 3, 'azerty', 'azertyuiop', 1, '2026-05-30 15:28:25'),
(8, 2, 'azerty', 'azertyuiop', 1, '2026-05-30 15:28:25'),
(9, 2, 'qsdfgh', 'qsdfghjklm', 10, '2026-05-30 15:28:25'),
(10, 2, 'qsdfgh', 'wxcvbn', 11, '2026-05-30 15:28:25'),
(11, 3, 'wxcvbn', 'wxcvbnmlkjh', 20, '2026-05-30 15:28:25'),
(12, 3, 'wxcvbn', 'wxcvbnmlkjh', 30, '2026-05-30 15:28:25'),
(13, 3, 'wxcvbn', 'wxcvbnmlkjh', 15, '2026-05-30 15:28:25'),
(14, 3, 'wxcvbn', 'wxcvbnmlkjh', 19, '2026-05-30 15:28:25'),
(15, 3, 'wxcvbn', 'wxcvbnmlkjh', 33, '2026-05-30 15:28:25'),
(16, 3, 'wxcvbn', 'wxcvbnmlkjh', 47, '2026-05-30 15:28:25'),
(17, 3, 'wxcvbn', 'wxcvbnmlkjh', 10, '2026-05-30 15:28:25'),
(18, 3, 'wxcvbn', 'wxcvbnmlkjh', 34, '2026-05-30 15:28:25'),
(19, 3, 'wxcvbn', 'wxcvbnmlkjh', 50, '2026-05-30 15:28:25'),
(20, 3, 'wxcvbn', 'wxcvbnmlkjh', 42, '2026-05-30 15:28:25'),
(21, 3, 'wxcvbn', 'wxcvbnmlkjh', 29, '2026-05-30 15:28:25'),
(22, 3, 'wxcvbn', 'wxcvbnmlkjh', 35, '2026-05-30 15:28:25'),
(23, 3, 'wxcvbn', 'wxcvbnmlkjh', 46, '2026-05-30 15:28:25'),
(24, 3, 'wxcvbn', 'wxcvbnmlkjh', 60, '2026-05-30 15:28:25'),
(25, 3, 'wxcvbn', 'wxcvbnmlkjh', 52, '2026-05-30 15:28:25'),
(26, 3, 'wxcvbn', 'wxcvbnmlkjh', 20, '2026-05-30 15:28:25'),
(27, 3, 'wxcvbn', 'wxcvbnmlkjh', 17, '2026-05-30 15:28:25'),
(28, 3, 'aaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaa\r\naaaaaaaaaaaaaaaaaaaaaaaaaaaa\r\naaaaaaaaaaaaaaaaaaaaaaaaaaaa\r\naaaaaaaaaaaaaaaaaaaaaaaaaaaa\r\naaaaaaaaaaaaaaaaaaaaaaaaaaaa\r\naaaaaaaaaaaaaaaaaaaaaaaaaaaa', 1, '2026-05-30 20:52:18');

-- --------------------------------------------------------

--
-- Table structure for table `article_likes`
--

CREATE TABLE `article_likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article_likes`
--

INSERT INTO `article_likes` (`id`, `user_id`, `article_id`) VALUES
(4, 2, 27),
(5, 2, 10),
(6, 3, 28);

-- --------------------------------------------------------

--
-- Table structure for table `challenges`
--

CREATE TABLE `challenges` (
  `id` int(11) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `challenges`
--

INSERT INTO `challenges` (`id`, `category`, `description`) VALUES
(1, 'Study & Academics', 'Read course material for 20 uninterrupted minutes.'),
(2, 'Study & Academics', 'Summarize today\'s lectures in 5 bullet points.'),
(3, 'Study & Academics', 'Organize your digital files and school folders.'),
(4, 'Study & Academics', 'Watch an educational video related to your major.'),
(5, 'Study & Academics', 'Create flashcards for an upcoming quiz/topic.'),
(6, 'Study & Academics', 'Write down your top 3 academic goals for the week.'),
(7, 'Study & Academics', 'Spend 15 minutes reviewing older notes.'),
(8, 'Study & Academics', 'Ask a question or participate actively in a class.'),
(9, 'Study & Academics', 'Clear your physical desk to create a clean study space.'),
(10, 'Study & Academics', 'Teach a complex concept you learned to someone else (or out loud to yourself).'),
(11, 'Deep Productivity', 'Plan your to-do list for tomorrow night.'),
(12, 'Deep Productivity', 'Work using the Pomodoro technique (25 mins work, 5 mins rest) for one cycle.'),
(13, 'Deep Productivity', 'Put your phone in another room for 1 hour while working.'),
(14, 'Deep Productivity', 'Unsubscribe from 1 distracting email newsletter.'),
(15, 'Deep Productivity', 'Complete the hardest task on your list first thing today.'),
(16, 'Deep Productivity', 'Limit social media usage to under 1 hour today.'),
(17, 'Deep Productivity', 'Brain dump: Write down everything on your mind to clear your head.'),
(18, 'Deep Productivity', 'Delete 1 app you no longer use from your phone.'),
(19, 'Deep Productivity', 'Reply to all pending emails or messages.'),
(20, 'Deep Productivity', 'Set a 10-minute timer and clean up as much of your room as possible.'),
(21, 'Sleep Hygiene', 'Turn off all screens 1 hour before going to bed.'),
(22, 'Sleep Hygiene', 'Get at least 7.5 hours of sleep tonight.'),
(24, 'Sleep Hygiene', 'Read a physical book before sleeping instead of using your phone.'),
(25, 'Sleep Hygiene', 'Do not consume any caffeine after 3:00 PM.'),
(26, 'Sleep Hygiene', 'Stretch for 5 minutes right before getting into bed.'),
(27, 'Sleep Hygiene', 'Wake up to your first alarm (no snoozing!).'),
(28, 'Sleep Hygiene', 'Make your bed within 5 minutes of waking up.'),
(29, 'Sleep Hygiene', 'Keep your bedroom cool and completely dark tonight.'),
(30, 'Sleep Hygiene', 'Write down 3 things you are grateful for right before sleeping.'),
(31, 'Nutrition & Hydration', 'Drink a glass of water immediately upon waking up.'),
(32, 'Nutrition & Hydration', 'Drink at least 2 liters of water throughout the day.'),
(33, 'Nutrition & Hydration', 'Eat at least one whole fruit today.'),
(34, 'Nutrition & Hydration', 'Include a serving of vegetables with your lunch or dinner.'),
(35, 'Nutrition & Hydration', 'Consume zero sugary drinks (soda, sweet tea) today.'),
(36, 'Nutrition & Hydration', 'Cook a meal at home instead of eating out.'),
(37, 'Nutrition & Hydration', 'Eat a meal mindfully (no TV, no phone, just focus on the food).'),
(38, 'Nutrition & Hydration', 'Avoid eating anything heavy 2 hours before bedtime.'),
(39, 'Nutrition & Hydration', 'Swap a junk food snack for a healthy alternative (e.g., nuts, yogurt).'),
(40, 'Nutrition & Hydration', 'Limit your coffee/energy drink intake to 1 cup today.'),
(41, 'Physical Vitality', 'Go for a 15-minute walk outside.'),
(42, 'Physical Vitality', 'Do 10 push-ups and 10 squats.'),
(43, 'Physical Vitality', 'Stand up and stretch for 2 minutes every hour you sit.'),
(44, 'Physical Vitality', 'Try a 10-minute beginner yoga routine on YouTube.'),
(45, 'Physical Vitality', 'Take the stairs instead of the elevator today.'),
(46, 'Physical Vitality', 'Do 5 minutes of core/ab exercises.'),
(47, 'Physical Vitality', 'Dance to 3 of your favorite high-energy songs.'),
(48, 'Physical Vitality', 'Correct your posture every time you catch yourself slouching.'),
(49, 'Physical Vitality', 'Spend 15 minutes getting direct sunlight.'),
(50, 'Physical Vitality', 'Do a light workout until you break a sweat.'),
(51, 'Mental Calmness & Stress', 'Practice deep breathing (4 seconds in, 4 hold, 4 out) for 3 minutes.'),
(52, 'Mental Calmness & Stress', 'Do a 5-minute guided meditation.'),
(53, 'Mental Calmness & Stress', 'Write down a current worry and logically challenge it.'),
(54, 'Mental Calmness & Stress', 'Spend 10 minutes doing absolutely nothing (no phone, no music).'),
(55, 'Mental Calmness & Stress', 'Listen to a calming playlist or nature sounds for 15 minutes.'),
(56, 'Mental Calmness & Stress', 'Color, doodle, or draw for 10 minutes just for fun.'),
(57, 'Mental Calmness & Stress', 'Forgive yourself for a mistake you made recently.'),
(58, 'Mental Calmness & Stress', 'Identify one source of stress today and write one way to minimize it.'),
(59, 'Mental Calmness & Stress', 'Take a completely cold shower for the last 30 seconds of your wash.'),
(60, 'Mental Calmness & Stress', 'Step outside and observe 5 things you can see, 4 you can feel, 3 you can hear.'),
(61, 'Social & Communication', 'Send a kind text to a friend you haven\'t spoken to in a while.'),
(62, 'Social & Communication', 'Give a genuine compliment to someone today.'),
(63, 'Social & Communication', 'Call a family member just to check in.'),
(64, 'Social & Communication', 'Practice active listening: let someone talk without interrupting them once.'),
(65, 'Social & Communication', 'Express gratitude to someone who helped you recently.'),
(66, 'Social & Communication', 'Ask a peer about their day and actually listen to the answer.'),
(67, 'Social & Communication', 'Leave a positive comment on a creator\'s post on any platform.'),
(68, 'Social & Communication', 'Say \"no\" to something you don\'t actually want to do (set a boundary).'),
(69, 'Social & Communication', 'Make eye contact and smile at a stranger.'),
(70, 'Social & Communication', 'Introduce yourself to someone new or someone you barely know.'),
(71, 'Personal Growth', 'Learn 3 new words in a foreign language.'),
(72, 'Personal Growth', 'Read one article (on SafeSpace or elsewhere) about a topic you know nothing about.'),
(73, 'Personal Growth', 'Listen to an educational or self-improvement podcast for 15 minutes.'),
(74, 'Personal Growth', 'Practice a hobby you love for 20 minutes.'),
(75, 'Personal Growth', 'Watch a TED Talk.'),
(76, 'Personal Growth', 'Identify one bad habit you have and write down a strategy to stop it.'),
(77, 'Personal Growth', 'Learn a new shortcut on your computer (e.g., Excel, VS Code, Word).'),
(78, 'Personal Growth', 'Read a chapter of a non-fiction book.'),
(79, 'Personal Growth', 'Write a letter to your future self 1 year from now.'),
(80, 'Personal Growth', 'Reflect on a recent failure and extract one lesson from it.'),
(81, 'Financial Habits', 'Track every single expense you make today.'),
(82, 'Financial Habits', 'Have a \"No Spend Day\" (buy absolutely nothing outside of necessities).'),
(83, 'Financial Habits', 'Review your bank account and check your current balance.'),
(84, 'Financial Habits', 'Pack your own lunch instead of buying it on campus.'),
(85, 'Financial Habits', 'Cancel one digital subscription you no longer use.'),
(86, 'Financial Habits', 'Put small change or a specific small amount into a savings account.'),
(87, 'Financial Habits', 'Compare the prices of an item before buying it.'),
(88, 'Financial Habits', 'Resist an impulse purchase (wait 24 hours before buying it).'),
(89, 'Financial Habits', 'Read one article about basic budgeting or investing.'),
(90, 'Financial Habits', 'Set a weekly budget limit for entertainment/snacks.'),
(91, 'Environment & Community', 'Pick up 3 pieces of litter and throw them in the trash.'),
(92, 'Environment & Community', 'Turn off lights/electronics in rooms you aren\'t using to save energy.'),
(93, 'Environment & Community', 'Donate an old item of clothing or a book.'),
(94, 'Environment & Community', 'Browse the \"Volunteering Places\" section on SafeSpace.'),
(95, 'Environment & Community', 'Use a reusable water bottle or coffee cup today instead of plastic.'),
(96, 'Environment & Community', 'Recycle your paper, plastic, or glass waste properly today.'),
(97, 'Environment & Community', 'Hold the door open for someone behind you.'),
(98, 'Environment & Community', 'Support a local small business instead of a major chain.'),
(99, 'Environment & Community', 'Share a helpful resource or link with a classmate.'),
(100, 'Environment & Community', 'Water a plant or spend 10 minutes tending to a garden/nature.'),
(111, 'Work & Career', 'Update your resume or LinkedIn profile with one new skill or experience.'),
(112, 'Work & Career', 'Reach out to a colleague, mentor, or professional connection just to say hello.'),
(113, 'Work & Career', 'Organize your work emails into folders and clear out your inbox.'),
(114, 'Work & Career', 'Write down 3 professional goals for the next 6 months.'),
(115, 'Work & Career', 'Spend 15 minutes researching an industry trend or new software in your field.'),
(116, 'Work & Career', 'End your workday with a clean desk and a prepared to-do list for tomorrow.'),
(117, 'Work & Career', 'Ask for constructive feedback on a recent project or task.'),
(118, 'Work & Career', 'Take a full lunch break completely away from your desk and screens.'),
(119, 'Work & Career', 'Practice a 30-second \"elevator pitch\" for your current role or the job you want.'),
(120, 'Work & Career', 'Acknowledge a coworker\'s achievement or thank them for their help on a task.');

-- --------------------------------------------------------

--
-- Table structure for table `creator_requests`
--

CREATE TABLE `creator_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_assignments`
--

CREATE TABLE `daily_assignments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_assignments`
--

INSERT INTO `daily_assignments` (`id`, `user_id`, `challenge_id`, `assigned_date`, `status`) VALUES
(1, 2, 1, '2026-04-22', 'completed'),
(2, 2, 12, '2026-04-22', 'completed'),
(3, 2, 22, '2026-04-22', 'completed'),
(4, 2, 38, '2026-04-22', 'completed'),
(5, 2, 43, '2026-04-22', 'completed'),
(6, 2, 52, '2026-04-22', 'completed'),
(7, 2, 67, '2026-04-22', 'completed'),
(8, 2, 75, '2026-04-22', 'completed'),
(9, 2, 88, '2026-04-22', 'completed'),
(10, 2, 92, '2026-04-22', 'completed'),
(11, 2, 112, '2026-04-22', 'completed'),
(23, 2, 8, '2026-04-23', 'completed'),
(24, 2, 14, '2026-04-23', 'completed'),
(25, 2, 28, '2026-04-23', 'completed'),
(26, 2, 36, '2026-04-23', 'completed'),
(27, 2, 46, '2026-04-23', 'completed'),
(28, 2, 54, '2026-04-23', 'completed'),
(29, 2, 67, '2026-04-23', 'completed'),
(30, 2, 73, '2026-04-23', 'completed'),
(31, 2, 86, '2026-04-23', 'completed'),
(32, 2, 97, '2026-04-23', 'completed'),
(33, 2, 113, '2026-04-23', 'completed'),
(34, 2, 3, '2026-04-24', 'completed'),
(35, 2, 12, '2026-04-24', 'completed'),
(36, 2, 28, '2026-04-24', 'completed'),
(37, 2, 36, '2026-04-24', 'pending'),
(38, 2, 50, '2026-04-24', 'pending'),
(39, 2, 52, '2026-04-24', 'pending'),
(40, 2, 62, '2026-04-24', 'pending'),
(41, 2, 80, '2026-04-24', 'pending'),
(42, 2, 86, '2026-04-24', 'pending'),
(43, 2, 98, '2026-04-24', 'pending'),
(44, 2, 112, '2026-04-24', 'pending'),
(45, 2, 1, '2026-04-25', 'pending'),
(46, 2, 15, '2026-04-25', 'pending'),
(47, 2, 30, '2026-04-25', 'pending'),
(48, 2, 37, '2026-04-25', 'pending'),
(49, 2, 42, '2026-04-25', 'pending'),
(50, 2, 52, '2026-04-25', 'pending'),
(51, 2, 66, '2026-04-25', 'pending'),
(52, 2, 79, '2026-04-25', 'pending'),
(53, 2, 84, '2026-04-25', 'pending'),
(54, 2, 97, '2026-04-25', 'pending'),
(55, 2, 120, '2026-04-25', 'pending'),
(56, 2, 5, '2026-05-23', 'completed'),
(57, 2, 20, '2026-05-23', 'completed'),
(58, 2, 27, '2026-05-23', 'completed'),
(59, 2, 32, '2026-05-23', 'completed'),
(60, 2, 50, '2026-05-23', 'completed'),
(61, 2, 53, '2026-05-23', 'completed'),
(62, 2, 63, '2026-05-23', 'completed'),
(63, 2, 74, '2026-05-23', 'completed'),
(64, 2, 90, '2026-05-23', 'pending'),
(65, 2, 94, '2026-05-23', 'pending'),
(66, 2, 113, '2026-05-23', 'pending'),
(70, 2, 5, '2026-05-20', 'completed'),
(71, 2, 12, '2026-05-22', 'completed'),
(78, 2, 2, '2026-05-29', 'completed'),
(79, 2, 20, '2026-05-30', 'pending'),
(80, 2, 22, '2026-05-30', 'pending'),
(81, 2, 34, '2026-05-30', 'pending'),
(82, 2, 49, '2026-05-30', 'pending'),
(83, 2, 54, '2026-05-30', 'pending'),
(84, 2, 65, '2026-05-30', 'pending'),
(85, 2, 74, '2026-05-30', 'pending'),
(86, 2, 87, '2026-05-30', 'pending'),
(87, 2, 94, '2026-05-30', 'pending'),
(88, 2, 112, '2026-05-30', 'pending'),
(89, 3, 10, '2026-05-30', 'pending'),
(90, 3, 14, '2026-05-30', 'pending'),
(91, 3, 24, '2026-05-30', 'pending'),
(92, 3, 33, '2026-05-30', 'pending'),
(93, 3, 42, '2026-05-30', 'pending'),
(94, 3, 56, '2026-05-30', 'pending'),
(95, 3, 70, '2026-05-30', 'pending'),
(96, 3, 77, '2026-05-30', 'pending'),
(97, 3, 88, '2026-05-30', 'pending'),
(98, 3, 91, '2026-05-30', 'pending'),
(99, 3, 118, '2026-05-30', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','creator','user') DEFAULT 'user',
  `points` int(11) DEFAULT 0,
  `streak` int(11) DEFAULT 0,
  `freezes` int(11) DEFAULT 2,
  `last_check_date` date DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `points`, `streak`, `freezes`, `last_check_date`, `reset_token`, `reset_token_expires_at`) VALUES
(1, 'System Admin', 'maysam.abdelli@sesame.com.tn', '$2y$10$lSsT0d8CjhidQN9l07BH3.cwkxTk6XAcwK.kNKzAlslPsUPYaG/V.', 'admin', 0, 0, 2, '2026-05-23', NULL, NULL),
(2, 'Maysam Abdelli', 'maysamabdelli195@gmail.com', '$2y$10$e8.Li8otsGBH5r7ILSu9O.wTya1YLDTrKsplftrFqcX35WL.E0kiK', 'user', 90, 3, 2, '2026-05-30', 'b43120ed1e474c5c48ab841187e7f813b8c43b75b1a638f8a5e440d7adddb64b', '2026-05-30 22:24:28'),
(3, 'Ranim Ben Ahmed', 'benahmed.ranim@sesame.com.tn', '$2y$10$Ly/yyedgpUq2RSGD2bM26uiNX5MMfAsyWfeCTueMBcCyF.VJ.1rxK', 'creator', 0, 0, 2, '2026-05-30', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `volunteering`
--

CREATE TABLE `volunteering` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `external_link` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `volunteering`
--

INSERT INTO `volunteering` (`id`, `title`, `description`, `location`, `external_link`) VALUES
(3, 'azerty', 'azertyuiopqsdfghjklm', 'azertyuiop', 'https://cosmetics-stock.web.app'),
(4, 'qsdfgh', 'qsdfghjklmazertyuiop', 'qsdfghjklm', 'https://cosmetics-stock.web.app');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `app_ratings`
--
ALTER TABLE `app_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indexes for table `article_likes`
--
ALTER TABLE `article_likes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `article_id` (`article_id`);

--
-- Indexes for table `challenges`
--
ALTER TABLE `challenges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `creator_requests`
--
ALTER TABLE `creator_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `daily_assignments`
--
ALTER TABLE `daily_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `volunteering`
--
ALTER TABLE `volunteering`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `app_ratings`
--
ALTER TABLE `app_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `article_likes`
--
ALTER TABLE `article_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `challenges`
--
ALTER TABLE `challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `creator_requests`
--
ALTER TABLE `creator_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `daily_assignments`
--
ALTER TABLE `daily_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `volunteering`
--
ALTER TABLE `volunteering`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `app_ratings`
--
ALTER TABLE `app_ratings`
  ADD CONSTRAINT `app_ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `article_likes`
--
ALTER TABLE `article_likes`
  ADD CONSTRAINT `article_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_likes_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `creator_requests`
--
ALTER TABLE `creator_requests`
  ADD CONSTRAINT `creator_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_assignments`
--
ALTER TABLE `daily_assignments`
  ADD CONSTRAINT `daily_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_assignments_ibfk_2` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
