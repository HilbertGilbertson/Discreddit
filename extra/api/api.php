<?php
/**
 * Discreddit Sample API Implementation
 * @file api.php
 * @version 1.0
 * @author HilbertGilbertson
 * @url https://github.com/HilbertGilbertson/Discreddit
 */

require '/path/to/Discreddit.php';
require '/path/to/config.php';
require 'Discreddit.API.php';

/*
 * SAMPLE (and basic) Authentication
 *
 * If you're going to enable the API, it's up to you to ensure you protect this page. The sample API is very basic,
 * and not intended directly for production use.
 */

if (!isset($_POST['password']) || !password_verify($_POST['password'], '$2y$10$Rilm2LHPoQe.RgVOwDn7Xu0BC15ntSPbnpZ3l7/n6hHlUCLClrNVG')) {
    die("Invalid password!");
}

$DCR = new API($config);
$DCR->serve();

