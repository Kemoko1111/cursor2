<?php
// Redirect to browse-mentors.php
// This file exists in case any links are pointing to find-mentors.php instead of browse-mentors.php

header('Location: /browse-mentors.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit();
?>