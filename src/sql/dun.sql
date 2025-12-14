-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2025 at 12:35 PM
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
-- Database: `dun`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `idcmt` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`idcmt`, `post_id`, `user_id`, `content`, `created_at`) VALUES
(1, 11, 1, 'đẹp', '2025-10-26 18:48:10'),
(2, 10, 1, 'Tui cũng thấy nó đẹp thật', '2025-10-26 19:04:52'),
(3, 11, 1, 'hahaa', '2025-10-26 19:07:37'),
(4, 12, 1, '123', '2025-11-10 17:04:39'),
(5, 12, 8, 'Xinh iuuu', '2025-11-10 17:07:42'),
(6, 12, 11, 'abc', '2025-11-17 12:18:01');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `user1_id`, `user2_id`, `created_at`) VALUES
(1, 2, 3, '2025-10-21 15:52:45'),
(2, 2, 5, '2025-10-21 15:52:45'),
(3, 1, 9, '2025-10-30 16:08:53'),
(4, 6, 1, '2025-10-30 23:47:04'),
(5, 6, 9, '2025-10-31 00:05:24'),
(6, 6, 8, '2025-10-31 00:07:19');

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

CREATE TABLE `follows` (
  `id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `followed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`id`, `follower_id`, `following_id`, `followed_at`) VALUES
(1, 2, 3, '2025-10-21 15:46:07'),
(2, 3, 2, '2025-10-21 15:46:07'),
(3, 4, 2, '2025-10-21 15:46:07'),
(4, 5, 2, '2025-10-21 15:46:07'),
(5, 2, 5, '2025-10-21 15:46:07'),
(6, 1, 3, '2025-10-26 11:41:16'),
(7, 2, 1, '2025-10-26 11:41:47'),
(16, 1, 8, '2025-10-26 15:39:43'),
(21, 1, 2, '2025-10-30 16:08:27'),
(23, 6, 1, '2025-10-30 23:46:18'),
(28, 8, 1, '2025-11-11 02:57:10'),
(37, 1, 9, '2025-11-11 03:45:17'),
(38, 9, 1, '2025-11-17 05:04:25'),
(39, 11, 1, '2025-11-17 12:18:35'),
(40, 10, 9, '2025-11-17 17:58:46'),
(41, 9, 8, '2025-11-17 18:02:48'),
(42, 9, 3, '2025-11-17 18:28:36');

-- --------------------------------------------------------

--
-- Table structure for table `friends`
--

CREATE TABLE `friends` (
  `id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `became_friends_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `friends`
--

INSERT INTO `friends` (`id`, `user1_id`, `user2_id`, `became_friends_at`) VALUES
(1, 2, 3, '2025-10-21 15:46:35'),
(2, 2, 5, '2025-10-21 15:46:35'),
(3, 1, 9, '2025-11-17 05:04:25');

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `idlike` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `liked_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`idlike`, `post_id`, `user_id`, `liked_at`) VALUES
(2, 10, 1, '2025-10-29 15:50:08'),
(4, 2, 1, '2025-10-29 15:50:11'),
(6, 3, 1, '2025-10-29 15:50:28'),
(7, 11, 1, '2025-10-30 16:08:06'),
(8, 12, 1, '2025-10-30 23:40:21'),
(9, 12, 8, '2025-11-10 17:07:30'),
(10, 12, 11, '2025-11-17 12:17:54'),
(11, 12, 9, '2025-11-17 17:58:12'),
(12, 11, 10, '2025-11-17 17:58:50'),
(13, 10, 10, '2025-11-17 17:58:52'),
(14, 10, 9, '2025-11-17 18:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `type` varchar(20) NOT NULL,
  `attachment_url` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL,
  `reply_to_id` int(11) DEFAULT NULL,
  `conversation_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `content`, `sent_at`, `is_read`, `type`, `attachment_url`, `is_deleted`, `reply_to_id`, `conversation_id`) VALUES
(10, 2, 3, 'Chào bạn Thanh!', '2025-10-21 15:53:59', 1, 'text', NULL, 0, NULL, 1),
(11, 2, 3, 'Chào bạn Thanh!', '2025-10-21 15:54:15', 1, 'text', NULL, 0, NULL, 1),
(12, 2, 3, 'Chào bạn Thanh!', '2025-10-21 15:54:52', 1, 'text', NULL, 0, NULL, 1),
(13, 2, 3, NULL, '2025-10-21 15:54:52', 1, 'image', 'uploads/cat.jpg', 0, NULL, 1),
(15, 2, 3, 'Chào bạn Thanh!', '2025-10-21 15:56:26', 1, 'text', NULL, 0, NULL, 1),
(16, 2, 3, NULL, '2025-10-21 15:56:26', 1, 'image', 'uploads/cat.jpg', 0, NULL, 1),
(18, 2, 5, 'Gửi file nè!', '2025-10-21 15:57:04', 0, 'file', 'uploads/report.pdf', 0, NULL, 2),
(19, 2, 3, 'ê mày', '2025-10-26 02:54:33', 1, 'text', NULL, 0, NULL, 1),
(20, 3, 2, 'sao thế con :)', '2025-10-26 02:55:20', 0, 'text', NULL, 0, NULL, 1),
(23, 3, 2, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2025-10-26 03:02:26', 0, 'text', NULL, 0, NULL, 1),
(25, 3, 2, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2025-10-26 03:06:30', 0, 'text', NULL, 1, NULL, 1),
(26, 3, 2, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2025-10-26 03:06:37', 0, 'text', NULL, 1, NULL, 1),
(27, 1, 9, 'hi', '2025-10-30 16:08:53', 1, 'text', NULL, 0, NULL, 3),
(28, 6, 1, 'Em chào sếp ạ', '2025-10-30 23:47:04', 1, 'text', NULL, 0, NULL, 4),
(29, 6, 9, 'ê', '2025-10-31 00:05:24', 1, 'text', NULL, 0, NULL, 5),
(30, 6, 8, 'halooo', '2025-10-31 00:07:19', 0, 'text', NULL, 0, NULL, 6),
(31, 6, 1, 'sếp giúp em xem cái này với ạ', '2025-10-31 00:07:32', 1, 'text', NULL, 0, NULL, 4),
(32, 9, 1, 'jz', '2025-11-17 04:50:52', 1, 'text', NULL, 0, NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `idnotifi` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`idnotifi`, `user_id`, `type`, `content`, `is_read`, `created_at`) VALUES
(1, 1, 'Chào mừng', 'chào mừng bạn đã đăng nhập', 0, '2025-11-11 02:39:23'),
(2, 9, NULL, 'admin đã bắt đầu theo dõi bạn.', 0, '2025-11-11 03:43:05'),
(3, 9, NULL, 'admin đã bắt đầu theo dõi bạn.', 0, '2025-11-11 03:43:08'),
(4, 9, NULL, 'admin đã bắt đầu theo dõi bạn.', 0, '2025-11-11 03:44:17'),
(5, 9, NULL, 'admin đã bắt đầu theo dõi bạn.', 0, '2025-11-11 03:45:15'),
(6, 9, NULL, 'admin đã bắt đầu theo dõi bạn.', 0, '2025-11-11 03:45:17'),
(7, 1, NULL, 'xinchao123 đã bắt đầu theo dõi bạn.', 0, '2025-11-17 05:04:25'),
(8, 1, NULL, 'DuongNT đã bình luận về bài viết của bạn.', 0, '2025-11-17 12:18:01'),
(9, 1, NULL, 'DuongNT đã bắt đầu theo dõi bạn.', 0, '2025-11-17 12:18:35'),
(10, 9, NULL, 'nguyenthaiduong29082004 đã bắt đầu theo dõi bạn.', 0, '2025-11-17 17:58:46'),
(11, 8, NULL, 'xinchao123 đã bắt đầu theo dõi bạn.', 0, '2025-11-17 18:02:48'),
(12, 3, NULL, 'xinchao123 đã bắt đầu theo dõi bạn.', 0, '2025-11-17 18:28:36');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 9, '0d5029ee93448e15a41a34e8bda7b0bf', '2025-11-17 01:41:23', '2025-11-17 06:41:23'),
(2, 9, '2478f22ae5322730b0f92da2151dc4f1', '2025-11-17 05:56:42', '2025-11-17 10:56:42'),
(3, 9, '225eac1caf5ee98b43cf8a5608b67a82', '2025-11-17 09:50:14', '2025-11-17 14:50:14'),
(4, 10, '0c1facec7096c378709f5a98c600a9ab', '2025-11-17 09:50:50', '2025-11-17 14:50:50'),
(5, 10, '790154551351cc00cd908cb572e68020', '2025-11-17 09:53:19', '2025-11-17 14:53:19');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `idpost` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `topic_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`idpost`, `user_id`, `title`, `content`, `created_at`, `updated_at`, `topic_id`) VALUES
(1, 3, 'Cách học lập trình hiệu quả', 'Chia sẻ các phương pháp học lập trình cho người mới bắt đầu.', '2025-10-20 01:10:40', '2025-10-20 01:16:29', 8),
(2, 2, 'Giải bài toán hình học lớp 10', 'Phân tích và giải bài toán hình học phức tạp.', '2025-10-20 01:10:40', '2025-10-20 01:16:29', 2),
(3, 2, 'Tổng hợp mẹo học tiếng Anh', 'Các mẹo giúp cải thiện kỹ năng nghe và nói tiếng Anh.', '2025-10-20 01:10:40', '2025-10-20 01:16:29', 7),
(10, 8, NULL, 'Trang web này đẹp vô cùng các bạn ạ', '2025-10-26 01:45:09', '2025-10-26 01:45:09', 1),
(11, 9, 'Định lý pytago', 'Quá khó, có thể là xỉuuuu', '2025-10-26 10:59:54', '2025-10-26 10:59:54', 2),
(12, 1, 'Môn lịch sử khó học quá', 'Mỗi lần lên lớp nghe giảng là trong đầu chỉ nghĩ tới ngủ thôi, nhiều thứ ở thời kỳ phong kiến khó hiểu quá T_T', '2025-10-30 21:17:11', '2025-10-30 21:17:11', 6);

-- --------------------------------------------------------

--
-- Table structure for table `post_images`
--

CREATE TABLE `post_images` (
  `idpostimg` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_images`
--

INSERT INTO `post_images` (`idpostimg`, `post_id`, `image_url`, `caption`, `uploaded_at`) VALUES
(1, 1, 'src/img/code_tips.jpg', 'Mẹo học lập trình', '2025-10-20 01:12:11'),
(2, 2, 'src/img/geometry_diagram.jpg', 'Hình minh họa bài toán', '2025-10-20 01:12:11'),
(3, 3, 'src/img/english_tips.jpg', 'Mẹo học tiếng Anh', '2025-10-20 01:12:11'),
(7, 10, 'src/img/Screenshot_2025-07-11_165013_1761417909_4330.png', NULL, '2025-10-26 01:45:09'),
(8, 11, 'src/img/480e2c1ba53b629ba4b577a2a9f28321_1761451194_5386.jpeg', NULL, '2025-10-26 10:59:54'),
(9, 12, 'src/img/7d25a79109cd90ca5845af1842a4ee87_1761833831_9660.jpeg', NULL, '2025-10-30 21:17:11');

-- --------------------------------------------------------

--
-- Table structure for table `post_reports`
--

CREATE TABLE `post_reports` (
  `idreport` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `handled` tinyint(1) DEFAULT 0,
  `handled_by` int(11) DEFAULT NULL,
  `handled_at` datetime DEFAULT NULL,
  `admin_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_reports`
--

INSERT INTO `post_reports` (`idreport`, `post_id`, `reporter_id`, `reason`, `created_at`, `handled`, `handled_by`, `handled_at`, `admin_note`) VALUES
(1, 2, 1, 'Hình mờ', '2025-11-17 14:02:16', 1, 1, '2025-11-17 14:02:28', 'Dismissed');

-- --------------------------------------------------------

--
-- Table structure for table `search_history`
--

CREATE TABLE `search_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `query_text` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `search_history`
--

INSERT INTO `search_history` (`id`, `user_id`, `query_text`, `created_at`) VALUES
(16, 11, 'a', '2025-11-17 12:19:31'),
(22, 9, 'Khôi', '2025-11-17 18:02:43'),
(23, 9, 'thành', '2025-11-17 18:28:33');

-- --------------------------------------------------------

--
-- Table structure for table `topics`
--

CREATE TABLE `topics` (
  `id` int(11) NOT NULL,
  `topic_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topics`
--

INSERT INTO `topics` (`id`, `topic_name`, `description`, `created_at`) VALUES
(1, 'Đại học', 'Chia sẻ kinh nghiệm học tập và đời sống sinh viên', '2025-10-19 18:05:19'),
(2, 'Toán học', 'Kiến thức, bài tập và mẹo học môn Toán', '2025-10-19 18:05:19'),
(3, 'Vật lý', 'Thảo luận về lý thuyết và ứng dụng Vật lý', '2025-10-19 18:05:19'),
(4, 'Hóa học', 'Chia sẻ kiến thức và thực hành môn Hóa', '2025-10-19 18:05:19'),
(5, 'Ngữ văn', 'Phân tích tác phẩm, luyện viết và học văn học', '2025-10-19 18:05:19'),
(6, 'Lịch sử', 'Tìm hiểu các sự kiện và nhân vật lịch sử', '2025-10-19 18:05:19'),
(7, 'Tiếng Anh', 'Học từ vựng, ngữ pháp và luyện kỹ năng tiếng Anh', '2025-10-19 18:05:19'),
(8, 'Tin học', 'Lập trình, phần mềm và công nghệ thông tin trong học tập', '2025-10-19 18:05:19'),
(9, 'Kỹ năng mềm', 'Phát triển kỹ năng giao tiếp, làm việc nhóm, quản lý thời gian', '2025-10-19 18:05:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `iduser` int(11) NOT NULL,
  `username` varchar(45) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(45) DEFAULT NULL,
  `phone` varchar(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `gender` tinyint(4) DEFAULT NULL,
  `avt` varchar(255) DEFAULT NULL,
  `bg` varchar(255) DEFAULT NULL,
  `bio` varchar(100) DEFAULT NULL,
  `isonline` tinyint(4) NOT NULL,
  `role` varchar(10) NOT NULL,
  `created_at` datetime NOT NULL,
  `user_deleted` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`iduser`, `username`, `password`, `email`, `name`, `phone`, `date`, `gender`, `avt`, `bg`, `bio`, `isonline`, `role`, `created_at`, `user_deleted`) VALUES
(1, 'admin', '$2y$10$86chBT0zWYpUj7kqb1wdU.STFhIHlqW1hHKXbiQQvnLVU3luqwH6y', 'admin@gmail.com', 'admin', '0971613452', '2004-08-29', 0, 'src/img/hi.jpg', 'src/img/code_tips.jpg', 'Đây là admin của website', 0, 'admin', '2025-10-20 00:00:00', 0),
(2, 'duncuti2908', '$2y$10$bc6/jhSIBs0Qx5/4u/cLKuSOOF8MmEhkRTs.uZyTUFay0XpROnC4C', 'duncuti@gmail.com', 'Dương', '0971613452', '0000-00-00', 0, NULL, NULL, NULL, 0, 'customer', '2025-10-19 20:07:03', 0),
(3, 'vanthanh0401', '$2y$10$40jnnlb54OwSEnfcuROGruidTEMoZG/s2xfU7IhVlJJLwrc2Od8Fq', 'vanthanh@gmail.com', 'Văn Thành', '0866112222', NULL, NULL, 'src/img/english_tips.jpg', NULL, NULL, 0, 'customer', '2025-10-19 20:08:07', 0),
(4, 'hoango', '$2y$10$yI81CsmpXCVKHpsCkYkCMOLBwqlsPj5blZEgHEOhQaDyIOLekpdlu', 'hoa@gmail.com', 'Hoàng Ngô', '0912345678', '2000-05-10', 1, NULL, NULL, NULL, 1, 'customer', '2025-10-21 15:44:47', 0),
(5, 'bongbong', 'bongbong123', 'bong@gmail.com', 'Bông Bông', '0912345679', '2001-07-15', 0, NULL, NULL, NULL, 0, 'customer', '2025-10-21 15:44:47', 0),
(6, 'vutiendat', '$2y$10$yxSSzwlhFbxrSbVQH24c6O9FGNcBqYPmKE8kU9d1PosD4UbHlBfnq', 'dat@gmail.com', 'Vũ Tiến Đạt', '0912345680', '1999-03-22', 1, NULL, NULL, NULL, 1, 'customer', '2025-10-21 15:44:47', 1),
(8, 'duncuti290804', '$2y$10$Re8nT8ekhQLvUiXBm3.ZR.ntEm0HDADaba8WziStyQ2uVm0SDNcp6', 'Kii290820004@gmail.com', 'Minh Khôi', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'customer', '2025-10-26 01:41:39', 0),
(9, 'xinchao123', '$2y$10$iitiBoJwmZT/n9VTCYmN7u2hAwtvbQtw1EBe142wUJOPVY29RO6.e', 'nguyenthaiduong1111111111@gmail.com', 'Quang Tuấn', NULL, '2003-06-11', 0, NULL, NULL, NULL, 1, 'customer', '2025-10-26 10:35:19', 0),
(10, 'nguyenthaiduong29082004', '$2y$10$vMPAhmzwIP/UTxDOh/guMeww0DnRaA5K90tPamIIFWQL4rjjTSM8i', 'nguyenthaiduong29082004@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'customer', '2025-11-17 04:05:01', 0),
(11, 'DuongNT', '$2y$10$t4KSvqDVY6nPw/hkhguu8eELQZJfNDXoB9cMcj/eyPkEUyVtbe81C', 'DuongNT@int.T4Tek.co', 'Dương', NULL, '2004-08-29', 0, NULL, NULL, NULL, 0, 'customer', '2025-11-17 12:17:10', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_interactions`
--

CREATE TABLE `user_interactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `action` enum('view','like','comment','share','save') NOT NULL,
  `time_spent` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `score` float DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`idcmt`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user1_id` (`user1_id`,`user2_id`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `follower_id` (`follower_id`,`following_id`),
  ADD KEY `following_id` (`following_id`);

--
-- Indexes for table `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user1_id` (`user1_id`,`user2_id`),
  ADD KEY `user2_id` (`user2_id`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`idlike`),
  ADD UNIQUE KEY `post_id` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `fk_reply_to_message` (`reply_to_id`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`idnotifi`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`idpost`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `post_images`
--
ALTER TABLE `post_images`
  ADD PRIMARY KEY (`idpostimg`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `post_reports`
--
ALTER TABLE `post_reports`
  ADD PRIMARY KEY (`idreport`);

--
-- Indexes for table `search_history`
--
ALTER TABLE `search_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`iduser`);

--
-- Indexes for table `user_interactions`
--
ALTER TABLE `user_interactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`topic_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `idcmt` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `follows`
--
ALTER TABLE `follows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `idlike` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `idnotifi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `idpost` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `post_images`
--
ALTER TABLE `post_images`
  MODIFY `idpostimg` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `post_reports`
--
ALTER TABLE `post_reports`
  MODIFY `idreport` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `search_history`
--
ALTER TABLE `search_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `iduser` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_interactions`
--
ALTER TABLE `user_interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`idpost`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`iduser`);

--
-- Constraints for table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`iduser`),
  ADD CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`iduser`);

--
-- Constraints for table `friends`
--
ALTER TABLE `friends`
  ADD CONSTRAINT `friends_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`iduser`),
  ADD CONSTRAINT `friends_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`iduser`);

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`idpost`),
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`iduser`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_reply_to_message` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`),
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`iduser`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`iduser`),
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`iduser`);

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`iduser`),
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`);

--
-- Constraints for table `post_images`
--
ALTER TABLE `post_images`
  ADD CONSTRAINT `post_images_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`idpost`);

--
-- Constraints for table `user_interactions`
--
ALTER TABLE `user_interactions`
  ADD CONSTRAINT `user_interactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`iduser`),
  ADD CONSTRAINT `user_interactions_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`idpost`);

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`iduser`),
  ADD CONSTRAINT `user_preferences_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
