// Update profile - FIXED with Prepared Statement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $state = sanitize($_POST['state']);
    $zip = sanitize($_POST['zip']);
    $country = sanitize($_POST['country']);
    
    // FIXED: Using Prepared Statement
    $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=?, city=?, state=?, zip=?, country=? WHERE id=?");
    $stmt->bind_param("sssssssi", $name, $phone, $address, $city, $state, $zip, $country, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $name;
        setFlashMessage('success', 'Profile updated successfully');
        redirect('profile.php');
    }
    $stmt->close();
}