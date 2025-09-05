<?php
// Ensure user is logged in and is an admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo "<h1>Access Denied</h1>";
    echo "<p>You do not have permission to view this page.</p>";
    return;
}

require_once 'Student.php';
require_once 'Class.php';
require_once 'Stream.php';
require_once 'StudentImporter.php';

$studentModel = new Student($pdo);
$classModel = new SchoolClass($pdo);
$streamModel = new Stream($pdo);
$importer = new StudentImporter($pdo);

$message = '';
$error = '';

// Handle batch import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'batch_import') {
    if (isset($_FILES['student_file']) && $_FILES['student_file']['error'] == UPLOAD_ERR_OK) {
        $streamId = $_POST['stream_id'];
        if ($streamId) {
            $filePath = $_FILES['student_file']['tmp_name'];
            $result = $importer->import($filePath, $streamId);
            if ($result['success']) {
                $message = "Import complete. Successfully imported {$result['imported']} students. Encountered {$result['errors']} errors.";
            } else {
                $error = "Import failed: " . $result['message'];
            }
        } else {
            $error = "Please select a stream to import the students into.";
        }
    } else {
        $error = "File upload failed. Please try again.";
    }
}

// Handle file upload and CRUD actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['add_student', 'update_student'])) {
    $action = $_POST['action'];
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $otherName = $_POST['other_name'] ?? '';
    $lin = $_POST['lin'] ?? '';
    $streamId = $_POST['stream_id'] ?? 0;

    $photoPath = null;
    // Handle photo processing (prioritize base64 from webcam)
    $profilePhotosPath = 'profile_photos/';
    $uploadDir = UPLOADS_PATH . $profilePhotosPath;

    if (!empty($_POST['base64_photo'])) {
        $base64img = $_POST['base64_photo'];
        $imgData = str_replace('data:image/png;base64,', '', $base64img);
        $imgData = str_replace(' ', '+', $imgData);
        $imgData = base64_decode($imgData);

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid() . '.png';
        $filePath = $uploadDir . $fileName;

        if (file_put_contents($filePath, $imgData)) {
            $photoPath = UPLOADS_DIR . $profilePhotosPath . $fileName; // Store relative path
        } else {
            $error = "Failed to save captured photo.";
        }

    } elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
        // Fallback to standard file upload
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid() . '-' . basename($_FILES['profile_photo']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetPath)) {
            $photoPath = UPLOADS_DIR . $profilePhotosPath . $fileName; // Store relative path
        } else {
            $error = "Failed to upload profile photo.";
        }
    }

    if (!$error) {
        if ($action === 'add_student') {
            if ($studentModel->create($firstName, $lastName, $otherName, $lin, $streamId, $photoPath)) {
                $message = "Student created successfully.";
            } else {
                $error = "Failed to create student. A student with this LIN may already exist.";
            }
        } elseif ($action === 'update_student' && isset($_POST['student_id'])) {
            $studentId = $_POST['student_id'];
            if ($studentModel->update($studentId, $firstName, $lastName, $otherName, $lin, $streamId, $photoPath)) {
                $message = "Student updated successfully.";
            } else {
                $error = "Failed to update student. A student with this LIN may already exist.";
            }
        }
    }
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_student') {
    $studentId = $_POST['student_id'];
    if ($studentModel->delete($studentId)) {
        $message = "Student deleted successfully.";
    } else {
        $error = "Failed to delete student.";
    }
}

// Fetch data for display
$classFilter = $_GET['class_filter'] ?? null;
$streamFilter = $_GET['stream_filter'] ?? null;

if ($streamFilter) {
    $students = $studentModel->getAllByStream($streamFilter);
} elseif ($classFilter) {
    $students = $studentModel->getAllByClass($classFilter);
} else {
    $students = $studentModel->getAll();
}

$classes = $classModel->getAll();
$allStreams = $streamModel->getAllWithClass(); // Fetch once for all dropdowns
?>

<h1>Manage Students</h1>
<p>Add, edit, or remove student records.</p>

<?php if ($message): ?><div class="alert alert-success" role="alert"><?php echo $message; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo $error; ?></div><?php endif; ?>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form action="?page=manage_students" method="get" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="manage_students">
            <div class="col-md-4">
                <label for="class_filter" class="form-label">Filter by Class</label>
                <select name="class_filter" id="class_filter" class="form-select">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo (($_GET['class_filter'] ?? '') == $class['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="stream_filter" class="form-label">Filter by Stream</label>
                <select name="stream_filter" id="stream_filter" class="form-select">
                    <option value="">All Streams</option>
                    <!-- Options will be populated by JavaScript -->
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Filter Students</button>
            </div>
        </form>
    </div>
</div>


<div class="row mb-4">
    <div class="col-md-6">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            Add New Student
        </button>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#batchImportModal">
            Batch Import Students
        </button>
    </div>
</div>

<!-- Photo Capture and Crop Modal -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Take and Crop Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 text-center">
                        <h6>Camera</h6>
                        <div id="my_camera" style="width:320px; height:240px; border:1px solid black;"></div>
                        <br>
                        <button type="button" class="btn btn-primary" id="capture_btn">Capture Photo</button>
                    </div>
                    <div class="col-md-6 text-center">
                        <h6>Crop</h6>
                        <div id="crop_area"></div>
                        <button type="button" class="btn btn-success" id="crop_and_save_btn" disabled>Crop & Use This Photo</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>LIN</th>
                    <th>Class</th>
                    <th>Stream</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student):
                    $photoUrl = BASE_URL . '/' . htmlspecialchars($student['profile_photo_path']);
                ?>
                <tr>
                    <td><img src="<?php echo $photoUrl; ?>" alt="Photo" width="50" class="rounded-circle"></td>
                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['other_name'] . ' ' . $student['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['lin']); ?></td>
                    <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['stream_name']); ?></td>
                    <td>
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editStudentModal"
                            data-student-id="<?php echo $student['id']; ?>"
                            data-first-name="<?php echo htmlspecialchars($student['first_name']); ?>"
                            data-other-name="<?php echo htmlspecialchars($student['other_name']); ?>"
                            data-last-name="<?php echo htmlspecialchars($student['last_name']); ?>"
                            data-lin="<?php echo htmlspecialchars($student['lin']); ?>"
                            data-stream-id="<?php echo $student['stream_id']; ?>">
                            Edit
                        </button>
                        <form action="?page=manage_students" method="post" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                            <input type="hidden" name="action" value="delete_student">
                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?page=manage_students" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_student">
                    <!-- Form fields -->
                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Other Name (Optional)</label>
                        <input type="text" name="other_name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Learner ID (LIN)</label>
                        <input type="text" name="lin" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Class/Stream</label>
                        <select name="stream_id" class="form-select" required>
                            <option value="">Select a stream...</option>
                            <?php foreach ($allStreams as $stream): ?>
                                <option value="<?php echo $stream['id']; ?>">
                                    <?php echo htmlspecialchars($stream['class_name'] . ' - ' . $stream['stream_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Photo</label>
                        <input type="file" name="profile_photo" class="form-control" id="add_profile_photo">
                        <input type="hidden" name="base64_photo" id="add_base64_photo">
                        <button type="button" class="btn btn-secondary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#photoModal" data-form-type="add">
                            Take Photo
                        </button>
                        <img src="#" id="add_photo_preview" class="photo-preview d-none" alt="New Photo Preview">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Student</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?page=manage_students" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_student">
                    <input type="hidden" name="student_id" id="edit_student_id">
                    <!-- Form fields -->
                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Other Name (Optional)</label>
                        <input type="text" name="other_name" id="edit_other_name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Learner ID (LIN)</label>
                        <input type="text" name="lin" id="edit_lin" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Class/Stream</label>
                        <select name="stream_id" id="edit_stream_id" class="form-select" required>
                            <option value="">Select a stream...</option>
                            <?php
                            foreach ($allStreams as $stream):
                            ?>
                                <option value="<?php echo $stream['id']; ?>">
                                    <?php echo htmlspecialchars($stream['class_name'] . ' - ' . $stream['stream_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Profile Photo</label>
                        <input type="file" name="profile_photo" class="form-control" id="edit_profile_photo">
                        <input type="hidden" name="base64_photo" id="edit_base64_photo">
                        <button type="button" class="btn btn-secondary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#photoModal" data-form-type="edit">
                            Take Photo
                        </button>
                        <img src="#" id="edit_photo_preview" class="photo-preview d-none" alt="New Photo Preview">
                        <small class="form-text text-muted">Leave blank or take a new photo to keep the current one.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var editStudentModal = document.getElementById('editStudentModal');
    editStudentModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;

        var studentId = button.getAttribute('data-student-id');
        var firstName = button.getAttribute('data-first-name');
        var otherName = button.getAttribute('data-other-name');
        var lastName = button.getAttribute('data-last-name');
        var lin = button.getAttribute('data-lin');
        var streamId = button.getAttribute('data-stream-id');

        var modal = this;
        modal.querySelector('#edit_student_id').value = studentId;
        modal.querySelector('#edit_first_name').value = firstName;
        modal.querySelector('#edit_other_name').value = otherName;
        modal.querySelector('#edit_last_name').value = lastName;
        modal.querySelector('#edit_lin').value = lin;
        modal.querySelector('#edit_stream_id').value = streamId;
    });

    // --- Logic for Photo Capture and Cropping ---
    let croppie = null;
    let photoModal = document.getElementById('photoModal');
    let formType = 'add'; // 'add' or 'edit'

    photoModal.addEventListener('show.bs.modal', function (event) {
        // Determine if we are adding or editing
        formType = event.relatedTarget.getAttribute('data-form-type');

        // Initialize Webcam
        Webcam.set({
            width: 320,
            height: 240,
            image_format: 'jpeg',
            jpeg_quality: 90
        });
        Webcam.attach('#my_camera');

        // Initialize Croppie
        let cropArea = document.getElementById('crop_area');
        if (!croppie) {
            croppie = new Croppie(cropArea, {
                viewport: { width: 200, height: 200, type: 'square' },
                boundary: { width: 300, height: 240 },
                enableExif: true
            });
        }
        document.getElementById('crop_and_save_btn').disabled = true;
    });

    photoModal.addEventListener('hide.bs.modal', function () {
        Webcam.reset();
        // Destroy existing croppie instance if it exists to clear the image
        if (croppie) {
            let cropArea = document.getElementById('crop_area');
            cropArea.innerHTML = ''; // Clear the cropper
            croppie = null; // Let it be re-initialized next time
        }
    });

    document.getElementById('capture_btn').addEventListener('click', function() {
        Webcam.snap(function(data_uri) {
            croppie.bind({
                url: data_uri
            });
            document.getElementById('crop_and_save_btn').disabled = false;
        });
    });

    document.getElementById('crop_and_save_btn').addEventListener('click', function() {
        croppie.result({
            type: 'base64',
            size: { width: 400, height: 400 },
            format: 'png'
        }).then(function(base64) {
            // Put the base64 string into the correct hidden input and show preview
            if (formType === 'add') {
                document.getElementById('add_base64_photo').value = base64;
                let preview = document.getElementById('add_photo_preview');
                preview.src = base64;
                preview.classList.remove('d-none');
                // Clear the file input to ensure base64 is used
                document.getElementById('add_profile_photo').value = '';
            } else {
                document.getElementById('edit_base64_photo').value = base64;
                let preview = document.getElementById('edit_photo_preview');
                preview.src = base64;
                preview.classList.remove('d-none');
                document.getElementById('edit_profile_photo').value = '';
            }

            // Close the modal
            var modalInstance = bootstrap.Modal.getInstance(photoModal);
            modalInstance.hide();
        });
    });

    // --- Logic for Filter Dropdowns ---
    const classFilter = document.getElementById('class_filter');
    const streamFilter = document.getElementById('stream_filter');
    const initialStreamFilterValue = '<?php echo $_GET['stream_filter'] ?? ''; ?>';

    function updateStreamFilter(classId) {
        // Clear existing stream options
        streamFilter.innerHTML = '<option value="">All Streams</option>';

        if (!classId) {
            return; // No class selected
        }

        // Fetch streams for the selected class
        fetch(`?page=ajax_get_streams&class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                data.forEach(stream => {
                    const option = document.createElement('option');
                    option.value = stream.id;
                    option.textContent = stream.name;
                    streamFilter.appendChild(option);
                });
                // Set the previously selected value if it exists
                if (initialStreamFilterValue) {
                    streamFilter.value = initialStreamFilterValue;
                }
            })
            .catch(error => console.error('Error fetching streams:', error));
    }

    classFilter.addEventListener('change', () => {
        updateStreamFilter(classFilter.value);
    });

    // Initial population if a class is already selected on page load
    if (classFilter.value) {
        updateStreamFilter(classFilter.value);
    }

});
</script>

<!-- Batch Import Modal -->
<div class="modal fade" id="batchImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Batch Import Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?page=manage_students" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="batch_import">
                    <div class="mb-3">
                        <label for="import_stream_id" class="form-label">Import Into Stream</label>
                        <select name="stream_id" id="import_stream_id" class="form-select" required>
                            <option value="">Select a stream...</option>
                            <?php
                            $allStreams = $streamModel->getAllWithClass();
                            foreach ($allStreams as $stream):
                            ?>
                                <option value="<?php echo $stream['id']; ?>">
                                    <?php echo htmlspecialchars($stream['class_name'] . ' - ' . $stream['stream_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="student_file" class="form-label">Student Data File (.xlsx, .xls, .csv)</label>
                        <input type="file" name="student_file" id="student_file" class="form-control" required accept=".xlsx,.xls,.csv">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Upload and Import</button>
                        <a href="templates/student_import_template.csv" class="btn btn-secondary" download>Download Template</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
