<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Committee Management</title>
</head>
<body>
    <?php include('Navigation.php'); ?>
    
    <div class="main-content">
        <h1>CLUB COMMITTEE MANAGEMENT</h1>
        
        <div class="management-split">
            
            <div class="committee-list-section">
                <h3>Current Committee</h3>
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Student Details</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Aina Nasuha (CD23008)</td>
                            <td>President</td>
                            <td>
                                <button class="btn btn-outline">Edit</button> 
                                <button class="btn btn-danger-text">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="action-footer">
                    <button class="btn btn-primary">Save Changes</button>
                    <a href="CLubDetailsPage.php" class="btn btn-back">Back</a>
                </div>
            </div>

            <div class="assign-section">
                <h3>Assign Committee</h3>
                <label>Select Student</label>
                <select>
                    <option>Jannatul Najihah (CD23028)</option>
                    <option>Aiman Hakim (CA23060)</option>
                </select>
                
                <label>Position</label>
                <select>
                    <option>Secretary</option>
                    <option>Treasurer</option>
                    <option>Committee Member</option>
                </select>

                <button class="btn btn-primary btn-full">Assign Role</button>
            </div>
        </div>
    </div>
</body>
</html>