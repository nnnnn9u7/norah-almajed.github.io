-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 02, 2025 at 11:50 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `user`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@igm.com', 'super_admin', 1, '2025-11-20 12:39:04'),
(2, 'moderator', '$2y$10$KhN7e2g9Q5q9Q5q9Q5q9QO5q9Q5q9Q5q9Q5q9Q5q9Q5q9Q5q9Q5q', 'Content Moderator', 'moderator@igm.com', 'moderator', 1, '2025-11-20 12:39:04');

-- --------------------------------------------------------

--
-- Table structure for table `ai_generated_questions`
--

CREATE TABLE `ai_generated_questions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_content` longtext NOT NULL,
  `questionlevel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`questionlevel`)),
  `questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`questions`)),
  `ai_model` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_generated_questions`
--

INSERT INTO `ai_generated_questions` (`id`, `user_id`, `file_name`, `file_content`, `questionlevel`, `questions`, `ai_model`, `created_at`) VALUES
(2, 1, 'sdasd.txt', '\"تعد القدرة على التعلم والتطور من أهم السمات التي تميز الإنسان. فعبر التاريخ، تقدم العلم بفضل البحث المستمر والتجربة وتبادل المعرفة بين الأفراد. اليوم، أصبح الذكاء الاصطناعي أداة قوية تساعد الباحثين والمطورين على ابتكار حلول جديدة لمشكلات معقدة في مجالات الصحة والتعليم والصناعة. ولكن رغم تقدم التقنية، يبقى الإنسان هو العنصر الأساسي في توجيه هذه الأدوات بشكل أخلاقي ومسؤول لتحقيق الفائدة للجميع.\"', '{\"planning_style\":\"منظم\",\"problem_solving\":\"تحليلي\",\"learning_preference\":\"متوازن\",\"test_preference\":\"مختلط\",\"learning_type\":\"بصري\"}', '[{\"question\":\"What is considered one of the most important characteristics that distinguish humans from other beings? (Page 1)\",\"options\":[\"Physical strength\",\"Ability to learn and evolve\",\"Technological advancements\",\"Environmental adaptations\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"points\":2,\"time_limit\":90},{\"question\":\"What has driven the progress of science throughout history? (Page 1)\",\"options\":[\"Random discoveries\",\"Continuous research, experimentation, and knowledge sharing\",\"Sole reliance on technology\",\"Isolation of individuals\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"points\":2,\"time_limit\":90},{\"question\":\"What role does artificial intelligence play in modern times? (Page 2)\",\"options\":[\"It replaces human workers in industries\",\"It assists researchers and developers in creating new solutions\",\"It is solely used for entertainment purposes\",\"It is a tool for environmental conservation\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"points\":2,\"time_limit\":90},{\"question\":\"In which fields are new solutions being developed with the help of artificial intelligence? (Page 2)\",\"options\":[\"Only in the field of education\",\"Only in the field of health\",\"In areas such as health, education, and industry\",\"In areas unrelated to human development\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"points\":2,\"time_limit\":90},{\"question\":\"Despite advancements in technology, what remains crucial for directing these tools ethically and responsibly? (Page 3)\",\"options\":[\"The development of more sophisticated algorithms\",\"The role of artificial intelligence itself\",\"Human involvement and guidance\",\"The reduction of technological use\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"points\":2,\"time_limit\":90},{\"question\":\"What is the ultimate goal of using artificial intelligence and other technological advancements? (Page 3)\",\"options\":[\"To replace human workers completely\",\"To benefit a select group of individuals\",\"To achieve benefits for all\",\"To solely drive economic growth\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"points\":2,\"time_limit\":90},{\"question\":\"How does the ability to learn and evolve contribute to human progress? (Page 1)\",\"options\":[\"It hinders progress by making humans too adaptable\",\"It has no significant impact on human development\",\"It allows for continuous improvement and innovation\",\"It leads to stagnation in scientific research\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"points\":2,\"time_limit\":90},{\"question\":\"What type of learning style is supported by the idea of balanced progress through continuous research and knowledge sharing? (Page 1)\",\"options\":[\"Visual learning\",\"Auditory learning\",\"Balanced and analytical learning\",\"Kinesthetic learning\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"points\":2,\"time_limit\":90},{\"question\":\"Why is it important for humans to be involved in the development and use of technological tools like artificial intelligence? (Page 3)\",\"options\":[\"Because humans are superior to machines\",\"Because machines cannot operate without human input\",\"To ensure these tools are used ethically and responsibly\",\"To reduce the cost of technological development\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"points\":2,\"time_limit\":90}]', 'AI Assistant Pro', '2025-11-01 19:48:27'),
(3, 2, 'T9 Package Diagram short.pdf', 'C:\\\\Documents and Settings\\\\Administrateur.STANDARD\\\\Bureau\\\\Site web de Vincent Barreaud_fichiers\\\\clipanime_e-mail_.gif KFU Logo. click to go to main C:\\\\Documents and Settings\\\\Administrateur.STANDARD\\\\Bureau\\\\Site web de Vincent Barreaud_fichiers\\\\clipanime_e-mail_.gif Package members shown within the package rectangle. Package shown as a rectangle with a small tab. Members of package shown outside the package. Package merge shown as dashed line with an open arrowhead from receiving package to merged package. Identity Identity iOS Version 18.6.2 \\(Build 22G100\\ D:20251118221906Z00\'00\' D:20251118221906Z00\'00\'', '{\"planning_style\":\"Organized\",\"problem_solving\":\"Analytical\",\"test_preference\":\"Mixed\",\"learning_type\":\"Visual\"}', '[{\"question\":\"purpose of the file extension \\\".gif\\\"?\",\"options\":[\"It is the file extension of the logo\",\"It is the file extension of the site\",\"It is the file extension of the package\",\"It is the build number of the iOS\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Easy\",\"page_unit\":\"1\",\"points\":2,\"time_limit\":60},{\"question\":\"significance of the folder name \\\"Bureau\\\"?\",\"options\":[\"It is the name of the site\",\"It is the name of the package\",\"It is the name of the folder containing the site\",\"It is the file extension of the logo\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Easy\",\"page_unit\":\"1\",\"points\":2,\"time_limit\":60},{\"question\":\"relationship between the logo and the site?\",\"options\":[\"The logo is part of the site\",\"The site is part of the logo\",\"The logo and site are separate\",\"The logo and site are merged\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Easy\",\"page_unit\":\"1\",\"points\":2,\"time_limit\":60},{\"question\":\"relationship between the site and the username?\",\"options\":[\"The site is owned by the username\",\"The username is part of the site\",\"The site and username are separate\",\"The site and username are merged\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Easy\",\"page_unit\":\"1\",\"points\":2,\"time_limit\":60},{\"question\":\"purpose of the version number 18.6.2?\",\"options\":[\"It is the version number of the package\",\"It is the build number of the iOS\",\"It is the version number of the iOS\",\"It is the file extension of the logo\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Easy\",\"page_unit\":\"1\",\"points\":2,\"time_limit\":60},{\"question\":\"version of the iOS?\",\"options\":[\"18.6.2\",\"22G100\",\"D:20251118221906Z00\'00\'\",\"Build 22G100\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Easy\",\"page_unit\":\"1\",\"points\":2,\"time_limit\":60},{\"question\":\"implication of the build number 22G100 on the package and its members?\",\"options\":[\"It is the build number of the package\",\"It is the build number of the iOS\",\"It is the build number that the package is compatible with\",\"It is the file extension of the logo\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"difficulty\":\"Easy\",\"page_unit\":\"1\",\"points\":2,\"time_limit\":60},{\"question\":\"significance of the folder name \\\"Site web de Vincent Barreaud\\\"?\",\"options\":[\"It is the name of the site\",\"It is the name of the package\",\"It is the name of the folder containing the site\",\"It is the file extension of the logo\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Easy\",\"page_unit\":\"1\",\"points\":2,\"time_limit\":60},{\"question\":\"relationship between the package members and the package rectangle?\",\"options\":[\"The package members are inside the package rectangle\",\"The package members are outside the package rectangle\",\"The package members are separate from the package rectangle\",\"The package members are merged with the package rectangle\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"difficulty\":\"Easy\",\"page_unit\":\"1\",\"points\":2,\"time_limit\":60},{\"question\":\"significance of the date \\\"D:20251118221906Z00\'00\'\\\"?\",\"options\":[\"It is the creation date of the package\",\"It is the modification date of the package\",\"It is the build date of the iOS\",\"It is the file extension of the logo\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Easy\",\"page_unit\":\"1\",\"points\":2,\"time_limit\":60},{\"question\":\"location of the package members?\",\"options\":[\"Inside the package\",\"Outside the package\",\"On the logo\",\"In the site\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"difficulty\":\"Easy\",\"page_unit\":\"1\",\"points\":2,\"time_limit\":60},{\"question\":\"purpose of the file name \\\"clipanime_e-mail_.gif\\\"?\",\"options\":[\"It is the name of the logo\",\"It is the name of the site\",\"It is the name of the package\",\"It is the build number of the iOS\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Easy\",\"page_unit\":\"1\",\"points\":2,\"time_limit\":60},{\"question\":\"relationship between the site and the package?\",\"options\":[\"The site contains the package\",\"The package contains the site\",\"The site and package are separate\",\"The site and package are merged\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"implication of the package merge on the relationships between packages?\",\"options\":[\"It creates a new package\",\"It deletes a package\",\"It changes the relationships between packages\",\"It has no effect on the relationships between packages\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"relationship between the package and the folder?\",\"options\":[\"The package is inside the folder\",\"The folder is inside the package\",\"The package and folder are separate\",\"The package and folder are merged\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"name of the folder?\",\"options\":[\"Bureau\",\"Site web de Vincent Barreaud\",\"Package members\",\"KFU Logo\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"significance of the iOS version 18.6.2?\",\"options\":[\"It is the latest version of the iOS\",\"It is the build number of the iOS\",\"It is the version number of the package\",\"It is the file extension of the logo\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"relationship between the site and the build number?\",\"options\":[\"The site is part of the build number\",\"The build number is part of the site\",\"The site and build number are separate\",\"The site and build number are merged\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"purpose of the build number \\\"22G100\\\"?\",\"options\":[\"It is the build number of the package\",\"It is the build number of the iOS\",\"It is the version number of the package\",\"It is the file extension of the logo\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"username in the path?\",\"options\":[\"Administrateur\",\"Vincent\",\"Bureau\",\"KFU\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"How does the package rectangle represent the package and its members?\",\"options\":[\"It shows the package and its members as separate entities\",\"It shows the package and its members as merged entities\",\"It shows the package and its members as related entities\",\"It shows the package and its members as unrelated entities\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"purpose of the package rectangle?\",\"options\":[\"To represent a package\",\"To represent a package member\",\"To represent a package merge\",\"To represent a logo\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"significance of the iOS version 18.6.2 in the context of the package and its members?\",\"options\":[\"It is the version number of the package\",\"It is the build number of the iOS\",\"It is the version number of the iOS that the package is compatible with\",\"It is the file extension of the logo\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"file name of the logo?\",\"options\":[\"clipanime_e-mail_.gif\",\"KFU Logo\",\"Site web de Vincent Barreaud\",\"Package members\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"purpose of the small tab on the package rectangle?\",\"options\":[\"To represent a package\",\"To represent a package member\",\"To represent a package merge\",\"To represent a logo\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"How does the package merge affect the relationships between packages, their members, and the iOS?\",\"options\":[\"It creates new relationships between packages, their members, and the iOS\",\"It deletes existing relationships between packages, their members, and the iOS\",\"It changes the existing relationships between packages, their members, and the iOS\",\"It has no effect on the relationships between packages, their members, and the iOS\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"significance of the path C:\\\\\\\\Documents and Settings\\\\\\\\Administrateur?\",\"options\":[\"It is the path to the site\",\"It is the path to the package\",\"It is the path to the folder containing the site\",\"It is the file extension of the logo\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"significance of the small tab on the package rectangle?\",\"options\":[\"To represent a package\",\"To represent a package member\",\"To represent a package merge\",\"To represent a logo\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"purpose of the package?\",\"options\":[\"To represent a logo\",\"To represent a site\",\"To represent a collection of members\",\"To represent a build number\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"How does the package rectangle represent the relationships between packages and their members?\",\"options\":[\"It shows the packages and their members as separate entities\",\"It shows the packages and their members as merged entities\",\"It shows the packages and their members as related entities\",\"It shows the packages and their members as unrelated entities\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"path to the site web de Vincent Barreaud?\",\"options\":[\"C:\\\\\\\\Documents and Settings\\\\\\\\Administrateur\",\"C:\\\\\\\\Documents and Settings\\\\\\\\Administrateur.STANDARD\\\\\\\\Bureau\\\\\\\\Site web de Vincent Barreaud_fichiers\",\"C:\\\\\\\\Documents and Settings\\\\\\\\Administrateur.STANDARD\\\\\\\\Bureau\",\"C:\\\\\\\\Documents and Settings\\\\\\\\Administrateur.STANDARD\\\\\\\\Bureau\\\\\\\\Site web de Vincent Barreaud_fichiers\\\\\\\\clipanime_e-mail_.gif\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"username in the path C:\\\\\\\\Documents and Settings\\\\\\\\Administrateur?\",\"options\":[\"Administrateur\",\"Vincent\",\"Bureau\",\"KFU\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"significance of the username \\\"Administrateur\\\"?\",\"options\":[\"It is the name of the site\",\"It is the name of the package\",\"It is the username of the user\",\"It is the file extension of the logo\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"significance of the date D:20251118221906Z00\'00\'?\",\"options\":[\"It is the creation date of the package\",\"It is the modification date of the package\",\"It is the build date of the iOS\",\"It is the file extension of the logo\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"purpose of the package merge?\",\"options\":[\"To show the members of a package\",\"To merge two packages\",\"To create a new package\",\"To delete a package\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"difficulty\":\"Medium\",\"page_unit\":\"1\",\"points\":3,\"time_limit\":90},{\"question\":\"purpose of the package rectangle?\",\"options\":[\"To represent a package\",\"To represent a package member\",\"To represent a package merge\",\"To represent a logo\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"name of the site?\",\"options\":[\"Site web de Vincent Barreaud\",\"KFU Logo\",\"Package members\",\"Bureau\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"relationship between the site and the folder?\",\"options\":[\"The site is inside the folder\",\"The folder is inside the site\",\"The site and folder are separate\",\"The site and folder are merged\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"definition of a package merge?\",\"options\":[\"A rectangle with a small tab\",\"A dashed line with an open arrowhead\",\"A member of a package\",\"A logo\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"build number of the iOS?\",\"options\":[\"18.6.2\",\"22G100\",\"D:20251118221906Z00\'00\'\",\"Build 22G100\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"How does the package merge affect the relationships between packages and their members?\",\"options\":[\"It creates new relationships between packages and their members\",\"It deletes existing relationships between packages and their members\",\"It changes the existing relationships between packages and their members\",\"It has no effect on the relationships between packages and their members\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"relationship between the package and its members?\",\"options\":[\"The package contains its members\",\"The package is contained by its members\",\"The package is separate from its members\",\"The package is merged with its members\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"file extension of the logo?\",\"options\":[\".gif\",\".jpg\",\".png\",\".bmp\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"significance of the build number 22G100?\",\"options\":[\"It is the version number of the iOS\",\"It is the build number of the iOS\",\"It is the file extension of the logo\",\"It is the package name\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"relationship between the package members and the package?\",\"options\":[\"The package members are inside the package\",\"The package members are outside the package\",\"The package members are separate from the package\",\"The package members are merged with the package\"],\"correct_answer\":1,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"significance of the date \\\"D:20251118221906Z00\'00\'\\\" in the context of the package and its members?\",\"options\":[\"It is the creation date of the package\",\"It is the modification date of the package\",\"It is the build date of the iOS that the package is compatible with\",\"It is the file extension of the logo\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"path to the logo?\",\"options\":[\"C:\\\\\\\\Documents and Settings\\\\\\\\Administrateur.STANDARD\\\\\\\\Bureau\\\\\\\\Site web de Vincent Barreaud_fichiers\\\\\\\\clipanime_e-mail_.gif\",\"C:\\\\\\\\Documents and Settings\\\\\\\\Administrateur\",\"C:\\\\\\\\Documents and Settings\\\\\\\\Administrateur.STANDARD\\\\\\\\Bureau\",\"C:\\\\\\\\Documents and Settings\\\\\\\\Administrateur.STANDARD\\\\\\\\Bureau\\\\\\\\Site web de Vincent Barreaud_fichiers\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"definition of a package?\",\"options\":[\"A rectangle with a small tab\",\"A dashed line with an open arrowhead\",\"A member of a package\",\"A logo\"],\"correct_answer\":0,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"function of the dashed line with an open arrowhead?\",\"options\":[\"To represent a package\",\"To represent a package member\",\"To represent a package merge\",\"To represent a logo\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120},{\"question\":\"relationship between the package and the iOS?\",\"options\":[\"The package is part of the iOS\",\"The iOS is part of the package\",\"The package and iOS are separate\",\"The package and iOS are merged\"],\"correct_answer\":2,\"type\":\"multiple_choice\",\"difficulty\":\"Hard\",\"page_unit\":\"1\",\"points\":5,\"time_limit\":120}]', 'AI Assistant Pro Enhanced', '2025-11-22 10:57:29');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','in_progress','resolved') DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `user_id`, `full_name`, `email`, `notes`, `status`, `admin_response`, `responded_by`, `responded_at`, `created_at`) VALUES
(1, NULL, 'Bashayer Jawad', 'xbii2014@gmail.com', 'fu', 'pending', NULL, NULL, NULL, '2025-11-22 11:38:06'),
(2, NULL, 'Norah Almajed', 'norah.almajed002@outlook.com', 'I love you so much, keep going!', 'in_progress', '', 1, '2025-12-01 11:12:44', '2025-11-30 22:07:04'),
(3, NULL, 'amnah ahmad', 'Amoni.ahmad21@gmail.com', 'ليش الموقع كذا يجنننن متى تنشرونها خياللللل', 'pending', NULL, NULL, NULL, '2025-12-02 21:17:39');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL DEFAULT '2024-01-01',
  `instructor` varchar(255) DEFAULT NULL,
  `format` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `price` decimal(10,2) DEFAULT 0.00,
  `certificate` varchar(50) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive','draft') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `external_link` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `description`, `date`, `instructor`, `format`, `duration`, `level`, `price`, `certificate`, `language`, `category`, `image_url`, `status`, `created_by`, `created_at`, `updated_at`, `external_link`) VALUES
(4, 'Cloud Computing Basics', 'Introduction to AWS, Azure, and Google Cloud platforms.', '2024-01-01', 'Michael Brown', NULL, '4 weeks', 'beginner', 49.00, NULL, NULL, 'Cloud', NULL, 'draft', 1, '2025-11-20 12:39:04', '2025-11-20 12:39:04', NULL),
(6, 'itc', 'itc', '2025-11-25', 'lolo', 'Live Online Sessions', '24 hours', 'advanced', 88.00, 'Yes', 'eng', NULL, 'uploads/courses/course_1763810811_69219dfb1be60.jpg', 'active', 1, '2025-11-22 11:26:51', '2025-11-22 11:26:51', 'https://www.linkedin.com/notifications/?filter=all'),
(7, 'Python for Everybody Specialization', 'What you\'ll learn\r\nThis Specialization builds on the success of the Python for Everybody course and will introduce fundamental programming concepts including data structures, networked application program interfaces, and databases, using the Python programming language. In the Capstone Project, you’ll use the technologies learned throughout the Specialization to design and create your own  applications for data retrieval, processing, and visualization.', '2025-12-03', 'Charles Russell Severance', 'Online Self-Paced', '2 months at 10 hours a week Learn at your own pace', 'beginner', 0.00, 'Yes', 'Englesh', NULL, 'uploads/courses/course_1764714108_692f667c037a5.png', 'active', 1, '2025-12-02 22:21:48', '2025-12-02 22:21:48', 'https://www.coursera.org/specializations/python');

-- --------------------------------------------------------

--
-- Table structure for table `exam_sessions`
--

CREATE TABLE `exam_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_set_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `time_spent` int(11) NOT NULL,
  `correct_answers` int(11) NOT NULL,
  `wrong_answers` int(11) NOT NULL,
  `average_time_per_question` float DEFAULT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_sessions`
--

INSERT INTO `exam_sessions` (`id`, `user_id`, `question_set_id`, `score`, `total_questions`, `time_spent`, `correct_answers`, `wrong_answers`, `average_time_per_question`, `status`, `created_at`) VALUES
(2, 1, 2, 44, 9, 148, 4, 5, 16.4444, 'completed', '2025-11-01 19:50:03'),
(3, 2, 3, 50, 12, 21, 6, 6, 1.75, 'completed', '2025-11-22 10:57:57');

-- --------------------------------------------------------

--
-- Table structure for table `hackathons`
--

CREATE TABLE `hackathons` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `format` varchar(100) DEFAULT NULL,
  `prize` varchar(255) DEFAULT NULL,
  `participants_limit` int(11) DEFAULT NULL,
  `registration_deadline` date DEFAULT NULL,
  `organizer` varchar(255) DEFAULT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `image_url` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `external_link` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hackathons`
--

INSERT INTO `hackathons` (`id`, `title`, `description`, `date`, `duration`, `location`, `format`, `prize`, `participants_limit`, `registration_deadline`, `organizer`, `status`, `image_url`, `created_by`, `created_at`, `updated_at`, `external_link`) VALUES
(3, 'Sustainability Tech Hack', 'Develop technology solutions for environmental sustainability and climate change.', '2025-02-10', '24 hours', 'Virtual', NULL, '$10,000', 100, '2025-02-05', NULL, 'ongoing', 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80', 1, '2025-11-20 12:39:04', '2025-11-20 12:50:02', NULL),
(7, 'أبشر طويـــــــــق', 'تسعى وزارة الموارد البشرية والتنمية الاجتماعية من خلال هاكاثون الابتكار للتغيير نحو الأفضل، إلى تحقيق أهداف رؤية المملكة العربية السعودية 2030. يتميز هذا الحدث بكونه منصة ملهمة تجمع العقول المبدعة والشغوفة بتطوير مستقبل أكثر كفاءة وابتكارًا، حيث يعزز منظومة التحول الرقمي ويرتقي بجودة الخدمات المقدمة للمستفيدين', '2025-11-29', '5', 'الرياض', 'In-Person', '$25,000', 4, '2025-12-17', 'اكاديمية الطويق', '', 'uploads/hackathons/hackathon_1764424953_692afcf98409e.png', 1, '2025-11-29 14:02:33', '2025-11-29 14:02:33', 'https://absher.tuwaiq.edu.sa/');

-- --------------------------------------------------------

--
-- Table structure for table `study_behavior_answers`
--

CREATE TABLE `study_behavior_answers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_number` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `answer_value` varchar(100) NOT NULL,
  `answer_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `study_behavior_answers`
--

INSERT INTO `study_behavior_answers` (`id`, `user_id`, `question_number`, `question_text`, `answer_value`, `answer_text`, `created_at`, `updated_at`) VALUES
(10, 1, 1, 'How do you plan a typical study day?', 'precise_timetable', 'I create a precise timetable', '2025-11-21 07:22:19', '2025-11-21 07:22:19'),
(11, 1, 2, 'When facing a difficult subject', 'additional_resources', 'I look for additional resources', '2025-11-21 07:22:19', '2025-11-21 07:22:19'),
(12, 1, 3, 'Preferred time to study:', 'afternoon', 'Afternoon', '2025-11-21 07:22:19', '2025-11-21 07:22:19'),
(13, 1, 4, 'What is your biggest challenge in studying?', 'poor_time_management', 'Poor time management', '2025-11-21 07:22:19', '2025-11-21 07:22:19'),
(14, 1, 5, 'When you forget a piece of information', 'visualize_layout', 'I visualize the page layout', '2025-11-21 07:22:19', '2025-11-21 07:22:19'),
(15, 1, 6, 'In the final exam, I believe what best measures my understanding is', 'mix_both', 'A mix of both', '2025-11-21 07:22:19', '2025-11-21 07:22:19'),
(16, 1, 7, 'When I face an essay question', 'handle_both_prefer_mc', 'I can handle both types, but I prefer multiple choice', '2025-11-21 07:22:19', '2025-11-21 07:22:19'),
(17, 1, 8, 'In multiple-choice questions', 'doubt_correct', 'I sometimes doubt which option is correct', '2025-11-21 07:22:19', '2025-11-21 07:22:19'),
(18, 1, 9, 'When preparing for an exam, I feel most comfortable when', 'prefer_short_answers', 'I prefer short questions with specific answers', '2025-11-21 07:22:19', '2025-11-21 07:22:19'),
(19, 2, 1, 'How do you plan a typical study day?', 'precise_timetable', 'I create a precise timetable', '2025-11-22 10:52:48', '2025-11-22 10:52:48'),
(20, 2, 2, 'When facing a difficult subject', 'ask_help', 'I ask for help immediately', '2025-11-22 10:52:48', '2025-11-22 10:52:48'),
(21, 2, 3, 'Preferred time to study:', 'evening', 'Evening', '2025-11-22 10:52:48', '2025-11-22 10:52:48'),
(22, 2, 4, 'What is your biggest challenge in studying?', 'poor_time_management', 'Poor time management', '2025-11-22 10:52:48', '2025-11-22 10:52:48'),
(23, 2, 5, 'When you forget a piece of information', 'visualize_layout', 'I visualize the page layout', '2025-11-22 10:52:48', '2025-11-22 10:52:48'),
(24, 2, 6, 'In the final exam, I believe what best measures my understanding is', 'essay_questions', 'Essay questions where I can explain my ideas', '2025-11-22 10:52:48', '2025-11-22 10:52:48'),
(25, 2, 7, 'When I face an essay question', 'prefer_multiple_choice', 'I\'d prefer it to be multiple choice', '2025-11-22 10:52:48', '2025-11-22 10:52:48'),
(26, 2, 8, 'In multiple-choice questions', 'prefer_combined', 'I prefer them when combined with essay questions', '2025-11-22 10:52:48', '2025-11-22 10:52:48'),
(27, 2, 9, 'When preparing for an exam, I feel most comfortable when', 'expect_objective', 'I expect objective questions (multiple choice)', '2025-11-22 10:52:48', '2025-11-22 10:52:48'),
(28, 3, 1, 'How do you plan a typical study day?', 'mood_circumstances', 'I leave it to my mood and circumstances', '2025-11-26 18:01:58', '2025-11-26 18:01:58'),
(29, 3, 2, 'When facing a difficult subject', 'postpone', 'I postpone it for later', '2025-11-26 18:01:58', '2025-11-26 18:01:58'),
(30, 3, 3, 'Preferred time to study:', 'night', 'Night', '2025-11-26 18:01:58', '2025-11-26 18:01:58'),
(31, 3, 4, 'What is your biggest challenge in studying?', 'poor_time_management', 'Poor time management', '2025-11-26 18:01:58', '2025-11-26 18:01:58'),
(32, 3, 5, 'When you forget a piece of information', 'solve_similar', 'I solve a similar problem again', '2025-11-26 18:01:58', '2025-11-26 18:01:58'),
(33, 3, 6, 'In the final exam, I believe what best measures my understanding is', 'mix_both', 'A mix of both', '2025-11-26 18:01:58', '2025-11-26 18:01:58'),
(34, 3, 7, 'When I face an essay question', 'anxious_organizing', 'I feel anxious about organizing my ideas', '2025-11-26 18:01:58', '2025-11-26 18:01:58'),
(35, 3, 8, 'In multiple-choice questions', 'doubt_correct', 'I sometimes doubt which option is correct', '2025-11-26 18:01:58', '2025-11-26 18:01:58'),
(36, 3, 9, 'When preparing for an exam, I feel most comfortable when', 'expect_objective', 'I expect objective questions (multiple choice)', '2025-11-26 18:01:58', '2025-11-26 18:01:58');

-- --------------------------------------------------------

--
-- Table structure for table `study_logs`
--

CREATE TABLE `study_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `study_date` date NOT NULL,
  `pages_studied` int(11) NOT NULL,
  `study_minutes` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `study_logs`
--

INSERT INTO `study_logs` (`id`, `user_id`, `schedule_id`, `study_date`, `pages_studied`, `study_minutes`, `notes`, `created_at`) VALUES
(7, 2, 9, '2025-11-22', 15, 90, NULL, '2025-11-22 11:15:22'),
(8, 2, 9, '2025-11-23', 15, 90, NULL, '2025-11-22 11:15:32'),
(9, 3, 12, '2025-12-07', 10, 90, NULL, '2025-12-01 11:18:06'),
(10, 3, 12, '2025-11-29', 10, 90, NULL, '2025-12-01 11:18:27'),
(11, 3, 12, '2025-11-30', 10, 90, NULL, '2025-12-01 11:18:30'),
(12, 3, 12, '2025-12-01', 10, 90, NULL, '2025-12-01 11:18:34');

-- --------------------------------------------------------

--
-- Table structure for table `study_schedules`
--

CREATE TABLE `study_schedules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `total_pages` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `study_hours` int(11) NOT NULL,
  `schedule_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`schedule_data`)),
  `learning_style` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`learning_style`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `study_schedules`
--

INSERT INTO `study_schedules` (`id`, `user_id`, `subject_name`, `total_pages`, `exam_date`, `study_hours`, `schedule_data`, `learning_style`, `created_at`) VALUES
(6, 1, 'FZ', 100, '2025-11-23', 6, '{\"total_pages\":\"100\",\"days_remaining\":3,\"daily_hours\":6,\"daily_plan\":[{\"date\":\"2025-11-20\",\"pages\":50,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 9 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 9 pages\"},{\"time\":\"10:00-10:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"10:00-11:00\",\"activity\":\"Study Session - 9 pages\"},{\"time\":\"11:00-11:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"11:00-12:00\",\"activity\":\"Study Session - 9 pages\"},{\"time\":\"12:00-12:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"12:00-13:00\",\"activity\":\"Study Session - 9 pages\"},{\"time\":\"13:00-13:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"13:00-14:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"14:00-14:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-11-21\",\"pages\":50,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 9 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 9 pages\"},{\"time\":\"10:00-10:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"10:00-11:00\",\"activity\":\"Study Session - 9 pages\"},{\"time\":\"11:00-11:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"11:00-12:00\",\"activity\":\"Study Session - 9 pages\"},{\"time\":\"12:00-12:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"12:00-13:00\",\"activity\":\"Study Session - 9 pages\"},{\"time\":\"13:00-13:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"13:00-14:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"14:00-14:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-11-22\",\"pages\":0,\"activity\":\"Comprehensive Review Day\",\"time_slots\":[{\"time\":\"09:00-10:00\",\"activity\":\"Review Key Concepts & Definitions\"},{\"time\":\"10:15-11:15\",\"activity\":\"Review Difficult Topics & Formulas\"},{\"time\":\"11:30-12:30\",\"activity\":\"Practice Questions & Solutions\"},{\"time\":\"14:00-15:00\",\"activity\":\"Final Summary & Important Points\"},{\"time\":\"16:00-16:30\",\"activity\":\"Quick Revision & Mental Preparation\"}]}]}', '{\"planning_style\":\"Organized\",\"problem_solving\":\"Analytical\",\"test_preference\":\"Mixed\",\"learning_type\":\"Visual\"}', '2025-11-20 13:28:15'),
(9, 2, 'ch1', 30, '2025-11-25', 2, '{\"total_pages\":\"30\",\"days_remaining\":3,\"daily_hours\":2,\"daily_plan\":[{\"date\":\"2025-11-22\",\"pages\":15,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 8 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 7 pages\"},{\"time\":\"10:00-10:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-11-23\",\"pages\":15,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 8 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 7 pages\"},{\"time\":\"10:00-10:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-11-24\",\"pages\":0,\"activity\":\"Comprehensive Review Day\",\"time_slots\":[{\"time\":\"09:00-10:00\",\"activity\":\"Review Key Concepts & Definitions\"},{\"time\":\"10:15-11:15\",\"activity\":\"Review Difficult Topics & Formulas\"},{\"time\":\"11:30-12:30\",\"activity\":\"Practice Questions & Solutions\"},{\"time\":\"14:00-15:00\",\"activity\":\"Final Summary & Important Points\"},{\"time\":\"16:00-16:30\",\"activity\":\"Quick Revision & Mental Preparation\"}]}]}', '{\"planning_style\":\"Organized\",\"problem_solving\":\"Analytical\",\"test_preference\":\"Mixed\",\"learning_type\":\"Visual\"}', '2025-11-22 11:15:10'),
(10, 2, 'pas', 60, '2025-11-25', 4, '{\"total_pages\":\"60\",\"days_remaining\":3,\"daily_hours\":4,\"daily_plan\":[{\"date\":\"2025-11-22\",\"pages\":30,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 8 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 8 pages\"},{\"time\":\"10:00-10:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"10:00-11:00\",\"activity\":\"Study Session - 8 pages\"},{\"time\":\"11:00-11:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"11:00-12:00\",\"activity\":\"Study Session - 6 pages\"},{\"time\":\"12:00-12:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-11-23\",\"pages\":30,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 8 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 8 pages\"},{\"time\":\"10:00-10:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"10:00-11:00\",\"activity\":\"Study Session - 8 pages\"},{\"time\":\"11:00-11:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"11:00-12:00\",\"activity\":\"Study Session - 6 pages\"},{\"time\":\"12:00-12:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-11-24\",\"pages\":0,\"activity\":\"Comprehensive Review Day\",\"time_slots\":[{\"time\":\"09:00-10:00\",\"activity\":\"Review Key Concepts & Definitions\"},{\"time\":\"10:15-11:15\",\"activity\":\"Review Difficult Topics & Formulas\"},{\"time\":\"11:30-12:30\",\"activity\":\"Practice Questions & Solutions\"},{\"time\":\"14:00-15:00\",\"activity\":\"Final Summary & Important Points\"},{\"time\":\"16:00-16:30\",\"activity\":\"Quick Revision & Mental Preparation\"}]}]}', '{\"planning_style\":\"Organized\",\"problem_solving\":\"Analytical\",\"test_preference\":\"Mixed\",\"learning_type\":\"Visual\"}', '2025-11-22 11:17:10'),
(12, 3, ' international business', 90, '2025-12-31', 2, '{\"total_pages\":\"90\",\"days_remaining\":32,\"daily_hours\":2,\"daily_plan\":[{\"date\":\"2025-11-29\",\"pages\":10,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"10:00-10:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-11-30\",\"pages\":10,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"10:00-10:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-12-01\",\"pages\":10,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"10:00-10:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-12-02\",\"pages\":10,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"10:00-10:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-12-03\",\"pages\":10,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"10:00-10:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-12-04\",\"pages\":10,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"10:00-10:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-12-05\",\"pages\":10,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"10:00-10:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-12-06\",\"pages\":10,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"10:00-10:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-12-07\",\"pages\":10,\"time_slots\":[{\"time\":\"8:00-9:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"9:00-9:15\",\"activity\":\"Short Break & Refresh\"},{\"time\":\"9:00-10:00\",\"activity\":\"Study Session - 5 pages\"},{\"time\":\"10:00-10:30\",\"activity\":\"Daily Review & Summary\"}]},{\"date\":\"2025-12-08\",\"pages\":0,\"activity\":\"Comprehensive Review Day\",\"time_slots\":[{\"time\":\"09:00-10:00\",\"activity\":\"Review Key Concepts & Definitions\"},{\"time\":\"10:15-11:15\",\"activity\":\"Review Difficult Topics & Formulas\"},{\"time\":\"11:30-12:30\",\"activity\":\"Practice Questions & Solutions\"},{\"time\":\"14:00-15:00\",\"activity\":\"Final Summary & Important Points\"},{\"time\":\"16:00-16:30\",\"activity\":\"Quick Revision & Mental Preparation\"}]}]}', '{\"planning_style\":\"Organized\",\"problem_solving\":\"Analytical\",\"test_preference\":\"Mixed\",\"learning_type\":\"Visual\"}', '2025-11-29 14:32:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `phone`, `password`, `created_at`) VALUES
(1, 'iAz', 'iAz', 'skolr7780@gmail.com', '0543453184', '$2y$10$VKoYhfETLGn3O2TI3ZkBBucn6lm9O0Sz945vyfVM2KWqSxqkN576y', '2025-11-01 19:27:52'),
(2, 'Bashayer Jawad', 'xbii2014', 'xbii2014@gmail.com', '0549033840', '$2y$10$.BYsgpni.rTyVuzIkj6aV..k2S9VX5pRslWtk.JUBzFYiB7N/8.Bi', '2025-11-22 10:52:22'),
(3, 'Norah Almajed', 'norah.almajed002', 'norah.almajed002@outlook.com', '0535796830', '$2y$10$dt5NXVXw2bHyKyly0s5eW.SYpaTbbyf7YUURJCu4QXuvtDQtD28K6', '2025-11-26 17:52:44');

-- --------------------------------------------------------

--
-- Table structure for table `user_answers`
--

CREATE TABLE `user_answers` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `question_number` int(11) NOT NULL,
  `user_answer` text NOT NULL,
  `correct_answer` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `time_spent` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_answers`
--

INSERT INTO `user_answers` (`id`, `session_id`, `question_number`, `user_answer`, `correct_answer`, `is_correct`, `time_spent`, `created_at`) VALUES
(10, 2, 1, '1', '1', 0, 60, '2025-11-01 19:50:03'),
(11, 2, 2, '2', '1', 0, 60, '2025-11-01 19:50:03'),
(12, 2, 3, '1', '1', 0, 60, '2025-11-01 19:50:03'),
(13, 2, 4, '2', '2', 0, 60, '2025-11-01 19:50:03'),
(14, 2, 5, '1', '2', 0, 60, '2025-11-01 19:50:03'),
(15, 2, 6, '2', '2', 0, 60, '2025-11-01 19:50:03'),
(16, 2, 7, '3', '2', 0, 60, '2025-11-01 19:50:03'),
(17, 2, 8, '1', '2', 0, 60, '2025-11-01 19:50:03'),
(18, 2, 9, '0', '2', 0, 60, '2025-11-01 19:50:03'),
(19, 3, 1, 'It is the file extension of the logo', 'It is the file extension of the logo', 1, 60, '2025-11-22 10:57:57'),
(20, 3, 2, 'It is the name of the folder containing the site', 'It is the name of the folder containing the site', 1, 60, '2025-11-22 10:57:57'),
(21, 3, 3, 'The logo and site are separate', 'The logo is part of the site', 0, 60, '2025-11-22 10:57:57'),
(22, 3, 4, 'The site and username are separate', 'The site is owned by the username', 0, 60, '2025-11-22 10:57:57'),
(23, 3, 5, 'It is the version number of the iOS', 'It is the version number of the iOS', 1, 60, '2025-11-22 10:57:57'),
(24, 3, 6, '22G100', '18.6.2', 0, 60, '2025-11-22 10:57:57'),
(25, 3, 7, 'It is the build number of the iOS', 'It is the build number of the iOS', 1, 60, '2025-11-22 10:57:57'),
(26, 3, 8, 'It is the name of the folder containing the site', 'It is the name of the folder containing the site', 1, 60, '2025-11-22 10:57:57'),
(27, 3, 9, 'The package members are outside the package rectangle', 'The package members are outside the package rectangle', 1, 60, '2025-11-22 10:57:57'),
(28, 3, 10, 'It is the modification date of the package', 'It is the build date of the iOS', 0, 60, '2025-11-22 10:57:57'),
(29, 3, 11, 'Inside the package', 'Outside the package', 0, 60, '2025-11-22 10:57:57'),
(30, 3, 12, 'It is the build number of the iOS', 'It is the name of the logo', 0, 60, '2025-11-22 10:57:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `ai_generated_questions`
--
ALTER TABLE `ai_generated_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `question_set_id` (`question_set_id`);

--
-- Indexes for table `hackathons`
--
ALTER TABLE `hackathons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `study_behavior_answers`
--
ALTER TABLE `study_behavior_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_question` (`user_id`,`question_number`);

--
-- Indexes for table `study_logs`
--
ALTER TABLE `study_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_study_day` (`user_id`,`schedule_id`,`study_date`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `study_schedules`
--
ALTER TABLE `study_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_answers`
--
ALTER TABLE `user_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ai_generated_questions`
--
ALTER TABLE `ai_generated_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hackathons`
--
ALTER TABLE `hackathons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `study_behavior_answers`
--
ALTER TABLE `study_behavior_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `study_logs`
--
ALTER TABLE `study_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `study_schedules`
--
ALTER TABLE `study_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_answers`
--
ALTER TABLE `user_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_generated_questions`
--
ALTER TABLE `ai_generated_questions`
  ADD CONSTRAINT `ai_generated_questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  ADD CONSTRAINT `exam_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_sessions_ibfk_2` FOREIGN KEY (`question_set_id`) REFERENCES `ai_generated_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hackathons`
--
ALTER TABLE `hackathons`
  ADD CONSTRAINT `hackathons_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `study_behavior_answers`
--
ALTER TABLE `study_behavior_answers`
  ADD CONSTRAINT `study_behavior_answers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `study_logs`
--
ALTER TABLE `study_logs`
  ADD CONSTRAINT `study_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `study_logs_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `study_schedules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `study_schedules`
--
ALTER TABLE `study_schedules`
  ADD CONSTRAINT `study_schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_answers`
--
ALTER TABLE `user_answers`
  ADD CONSTRAINT `user_answers_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `exam_sessions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
