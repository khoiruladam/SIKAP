<?php
session_start();
require '../config/koneksi.php';

if (!in_array($_SESSION['role'] ?? '', ['admin','guru'])) exit;

$id = (int)$_GET['id'];
mysqli_query($koneksi, "DELETE FROM agenda WHERE id=$id");
header("Location: dashboard.php");
