<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';
$pdo = getDB();

// --- 1. HANDLE DELETE ROOM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room'])) {
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE room_id = ?");
    $stmt->execute([$_POST['room_id']]);
    header("Location: manage-rooms.php?deleted=1");
    exit;
}

// --- 2. HANDLE INDIVIDUAL ROOM STATUS UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_single_status'])) {
    $room_id = $_POST['room_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE room_id = ?");
    if ($stmt->execute([$status, $room_id])) {
        header("Location: manage-rooms.php?updated=1");
        exit;
    }
}


// --- 3. HANDLE CREATE OR UPDATE ROOM TYPE DETAILS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['update_room_type']) || isset($_POST['add_room_type']))) {
    $room_type_id = $_POST['room_type_id'] ?? null;
    $name = $_POST['name'] ?? 'New Room Type';
    $base_price = $_POST['base_price'];
    $description = $_POST['description'];
    $selected_amenities = $_POST['amenities'] ?? [];
    $official_bed_type_id = $_POST['official_bed_type_id']; // Captured here

    try {
        $pdo->beginTransaction();

        if (isset($_POST['add_room_type'])) {
            // FIX: Added official_bed_type_id to the INSERT query
            $stmt = $pdo->prepare("INSERT INTO room_types (name, base_price, description, official_bed_type_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $base_price, $description, $official_bed_type_id]);
            $room_type_id = $pdo->lastInsertId();
        } else {
            // FIX: Added official_bed_type_id to the UPDATE query
            $pdo->prepare("UPDATE room_types SET base_price = ?, description = ?, official_bed_type_id = ? WHERE room_type_id = ?")
                ->execute([$base_price, $description, $official_bed_type_id, $room_type_id]);
        }

       $pdo->prepare("DELETE FROM room_type_amenities WHERE room_type_id = ?")->execute([$room_type_id]);
        foreach ($selected_amenities as $amenity_id) {
            $pdo->prepare("INSERT INTO room_type_amenities (room_type_id, amenity_id) VALUES (?, ?)")
                ->execute([$room_type_id, $amenity_id]);
        }

        // Handle Image Uploads (Max 10)
        if (!empty($_FILES['room_images']['name'][0])) {
            $files = $_FILES['room_images'];
            $upload_dir = '../../uploads/rooms/'; 
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $current_imgs = $pdo->prepare("SELECT COUNT(*) FROM room_type_images WHERE room_type_id = ?");
            $current_imgs->execute([$room_type_id]);
            $total_existing = $current_imgs->fetchColumn();

            foreach ($files['name'] as $key => $val) {
                if ($total_existing >= 10) break;
                if ($files['error'][$key] !== UPLOAD_ERR_OK) continue; // Skip files with errors

                $tmp_name = $files['tmp_name'][$key];
                // Use bin2hex(random_bytes(4)) for better uniqueness than time()
                $file_name = bin2hex(random_bytes(4)) . '_' . basename($files['name'][$key]);
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($tmp_name, $target_file)) {
                    $stmt = $pdo->prepare("INSERT INTO room_type_images (room_type_id, image_filename) VALUES (?, ?)");
                    $stmt->execute([$room_type_id, $file_name]);
                    $total_existing++;
                }
            }
        }

        $pdo->commit();
        header("Location: manage-rooms.php?success=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// --- 4. HANDLE ADD NEW ROOM (The Fix is here) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    // We use ?? to provide a fallback value so it never sends NULL to the database
    $room_number = $_POST['room_number'] ?? '';
    $room_type_id = $_POST['room_type_id'] ?? 0;
    $floor = (int)($_POST['floor'] ?? 1);
    $bed_type_id = (int)($_POST['bed_type_id'] ?? 1);
    $view_type_id = (int)($_POST['view_type_id'] ?? 1);
    $capacity = (int)($_POST['capacity'] ?? 1);
    
    // Ensure capacity is at least 1 to satisfy the database constraint
    if($capacity < 1) $capacity = 1;

    $sql = "INSERT INTO rooms (room_number, room_type_id, floor, bed_type_id, view_type_id, capacity, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'available')";
            
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$room_number, $room_type_id, $floor, $bed_type_id, $view_type_id, $capacity])) {
        header("Location: manage-rooms.php?success=1");
        exit;
    }
}

// --- 5. FETCH DATA FOR THE PAGE ---
$room_types_data = $pdo->query("
    SELECT rt.*, 
    COUNT(r.room_id) as total_rooms,
    SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_count,
    (SELECT GROUP_CONCAT(amenity_id) FROM room_type_amenities WHERE room_type_id = rt.room_type_id) as amenity_ids,
    (SELECT GROUP_CONCAT(CONCAT(room_id, ':', room_number, ':', status) ORDER BY room_number ASC SEPARATOR '|') 
     FROM rooms WHERE room_type_id = rt.room_type_id) as room_list
    FROM room_types rt
    LEFT JOIN rooms r ON rt.room_type_id = r.room_type_id
    GROUP BY rt.room_type_id
")->fetchAll();

$all_amenities = $pdo->query("SELECT * FROM amenities ORDER BY name ASC")->fetchAll();
$bed_types = $pdo->query("SELECT bed_type_id, name FROM bed_types ORDER BY name ASC")->fetchAll();
$view_types = $pdo->query("SELECT view_type_id, name FROM view_types ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Rooms — Hotelia Admin</title>
    <link rel="stylesheet" href="../../assets/css/tailwind.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 flex font-mulish min-h-screen">
    <main class="flex-grow p-8">
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="...">
                    <i data-lucide="arrow-left" class="w-6 h-6"></i>
                </a>
                <h2 class="text-3xl font-black text-teal-900 uppercase">Room Categories</h2>
            </div>
            <button onclick="openAddTypeModal()" class="flex items-center gap-2 px-6 py-3 bg-teal-600 text-white rounded-2xl font-black uppercase text-xs hover:bg-teal-700 transition-all shadow-lg shadow-teal-100">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Add Room Type
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?><div class="mb-6 p-4 bg-emerald-100 border border-emerald-200 text-emerald-700 rounded-xl font-bold">✅ Room added successfully!</div><?php endif; ?>
        <?php if (isset($_GET['updated'])): ?><div class="mb-6 p-4 bg-blue-100 border border-blue-200 text-blue-700 rounded-xl font-bold">🔄 Updated successfully!</div><?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?><div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-700 rounded-xl font-bold">🗑️ Room deleted.</div><?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach($room_types_data as $type): ?>
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col h-full hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-xl font-black text-teal-900"><?= htmlspecialchars($type['name']) ?></h3>
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-teal-50 text-teal-600 border border-teal-100">
                        <?= $type['available_count'] ?> / <?= $type['total_rooms'] ?> Avail
                    </span>
                </div>
                <p class="text-2xl font-bold text-teal-600 mb-2">₱<?= number_format((float)$type['base_price']) ?></p>
                <p class="text-xs text-gray-400 font-medium line-clamp-2 mb-6 flex-grow"><?= htmlspecialchars($type['description'] ?? 'No description.') ?></p>

                <div class="space-y-2 mt-auto">
                    <button onclick="openTypeModal(
                        '<?= $type['room_type_id'] ?>', 
                        '<?= addslashes($type['name']) ?>', 
                        '<?= $type['base_price'] ?>', 
                        '<?= addslashes($type['description'] ?? '') ?>', 
                        '<?= $type['amenity_ids'] ?? '' ?>',
                        '<?= $type['official_bed_type_id'] ?>' 
                    )" class="w-full py-3 bg-teal-600 text-white rounded-xl font-bold text-xs hover:bg-teal-700 transition-all">
                        Edit Type Details
                    </button>
                    <button onclick="openRoomsListModal('<?= $type['room_type_id'] ?>', '<?= addslashes($type['name']) ?>', '<?= $type['room_list'] ?? '' ?>')" 
                        class="w-full py-3 bg-gray-100 text-gray-600 rounded-xl font-bold text-xs hover:bg-gray-200 transition-all">
                        Manage Rooms (<?= $type['total_rooms'] ?>)
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="roomsListModal" class="fixed inset-0 bg-black/50 hidden z-[100] flex items-center justify-center backdrop-blur-sm px-4">
            <div class="bg-white rounded-3xl w-full max-w-2xl shadow-2xl flex flex-col max-h-[90vh]">
                <div class="p-8 pb-4 flex justify-between items-center border-b">
                    <h3 class="text-xl font-black uppercase text-teal-900"><span id="roomListTitle"></span> Rooms</h3>
                    <button onclick="closeRoomsListModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x"></i></button>
                </div>

                <div class="p-8 overflow-y-auto flex-grow">
                    <div class="mb-8">
                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-4">Existing Numbers</label>
                        <div id="roomItemsContainer" class="space-y-3"></div>
                    </div>

                    <div class="bg-teal-50 p-6 rounded-2xl border border-teal-100">
                        <h4 class="text-sm font-black text-teal-900 uppercase mb-4">Add Room</h4>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="room_type_id" id="addRoomTypeId">
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black uppercase text-teal-600">Room Number</label>
                                    <input type="text" name="room_number" placeholder="e.g. 101" class="w-full border-none p-3 rounded-xl font-bold bg-white text-sm" required>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase text-teal-600">Floor</label>
                                    <input type="number" name="floor" placeholder="1" class="w-full border-none p-3 rounded-xl font-bold bg-white text-sm" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="text-[10px] font-black uppercase text-teal-600">Capacity</label>
                                    <input type="number" name="capacity" value="2" min="1" class="w-full border-none p-3 rounded-xl font-bold bg-white text-sm" required>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase text-teal-600">Bed Type</label>
                                    <select name="bed_type_id" class="w-full border-none p-3 rounded-xl font-bold bg-white text-xs" required>
                                        <?php foreach($bed_types as $bt): ?><option value="<?= $bt['bed_type_id'] ?>"><?= $bt['name'] ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase text-teal-600">View</label>
                                    <select name="view_type_id" class="w-full border-none p-3 rounded-xl font-bold bg-white text-xs" required>
                                        <?php foreach($view_types as $vt): ?><option value="<?= $vt['view_type_id'] ?>"><?= $vt['name'] ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" name="add_room" class="w-full py-3 bg-teal-600 text-white rounded-xl font-black uppercase text-xs hover:bg-teal-700 transition-all">Create Room</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="typeModal" class="fixed inset-0 bg-black/50 hidden z-[100] flex items-center justify-center backdrop-blur-sm px-4">
            <div class="bg-white p-8 rounded-3xl w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
                <h3 class="text-2xl font-black mb-6 uppercase text-teal-900">Edit Category: <span id="editTypeLabel"></span></h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="room_type_id" id="editRoomTypeId">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-400 mb-1">Base Price (₱)</label>
                            <input type="number" name="base_price" id="editPrice" class="w-full border p-3 rounded-xl font-bold outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-400 mb-1">Bed Type</label>
                            <select name="official_bed_type_id" id="editBedType" class="w-full border p-3 rounded-xl font-bold bg-white text-sm" required>
                                <?php foreach($bed_types as $bt): ?>
                                    <option value="<?= $bt['bed_type_id'] ?>"><?= htmlspecialchars($bt['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase text-gray-400 mb-1">Add Photos (Max 10 total)</label>
                        <input type="file" name="room_images[]" multiple accept="image/*" class="w-full text-xs font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase text-gray-400 mb-1">Description</label>
                        <textarea name="description" id="editDescription" rows="2" class="w-full border p-3 rounded-xl font-bold outline-none"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 pt-4">
                        <button type="button" onclick="closeTypeModal()" class="py-4 bg-gray-100 text-gray-500 rounded-2xl font-black uppercase">Cancel</button>
                        <button type="submit" name="update_room_type" class="py-4 bg-teal-600 text-white rounded-2xl font-black uppercase">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        function openAddTypeModal() {
            document.getElementById('addTypeModal').classList.remove('hidden');
        }
        function closeAddTypeModal() {
            document.getElementById('addTypeModal').classList.add('hidden');
        }

        function openTypeModal(id, name, price, description, amenityIds, bedTypeId) {
            document.getElementById('editRoomTypeId').value = id;
            document.getElementById('editTypeLabel').textContent = name;
            document.getElementById('editPrice').value = price;
            document.getElementById('editDescription').value = description;
            
            // Set the dropdown value for the bed type
            if (bedTypeId) {
                document.getElementById('editBedType').value = bedTypeId;
            }

            const amenityArray = amenityIds ? amenityIds.split(',') : [];
            document.querySelectorAll('.amenity-checkbox').forEach(cb => {
                cb.checked = amenityArray.includes(cb.value);
            });
            
            document.getElementById('typeModal').classList.remove('hidden');
        }
        function closeTypeModal() { document.getElementById('typeModal').classList.add('hidden'); }

        function openRoomsListModal(typeId, typeName, roomDataStr) {
            document.getElementById('roomListTitle').textContent = typeName;
            document.getElementById('addRoomTypeId').value = typeId;
            const container = document.getElementById('roomItemsContainer');
            container.innerHTML = '';

            if (roomDataStr) {
                roomDataStr.split('|').forEach(roomStr => {
                    const [id, number, status] = roomStr.split(':');
                    const div = document.createElement('div');
                    div.className = "flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-100";
                    div.innerHTML = `
                        <div class="font-bold text-teal-900 text-sm uppercase">Room ${number}</div>
                        <div class="flex items-center gap-3">
                            <form method="POST" class="inline">
                                <input type="hidden" name="room_id" value="${id}">
                                <input type="hidden" name="update_single_status" value="1">
                                <select name="status" onchange="this.form.submit()" class="text-[10px] font-black uppercase border-none bg-white rounded-lg px-2 py-1 shadow-sm cursor-pointer ${status === 'available' ? 'text-emerald-600' : 'text-amber-600'}">
                                    <option value="available" ${status === 'available' ? 'selected' : ''}>Available</option>
                                    <option value="maintenance" ${status === 'maintenance' ? 'selected' : ''}>Maint.</option>
                                    <option value="occupied" ${status === 'occupied' ? 'selected' : ''} disabled>Occupied</option>
                                </select>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete Room ${number}?');">
                                <input type="hidden" name="room_id" value="${id}">
                                <button type="submit" name="delete_room" class="text-red-400 hover:text-red-600"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </form>
                        </div>`;
                    container.appendChild(div);
                });
                lucide.createIcons();
            } else {
                container.innerHTML = '<p class="text-center text-gray-400 py-4 font-bold text-sm italic">No rooms yet.</p>';
            }
            document.getElementById('roomsListModal').classList.remove('hidden');
        }
        function closeRoomsListModal() { document.getElementById('roomsListModal').classList.add('hidden'); }
    </script>
    <div id="addTypeModal" class="fixed inset-0 bg-black/50 hidden z-[100] flex items-center justify-center backdrop-blur-sm px-4">
        <div class="bg-white p-8 rounded-3xl w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-black mb-6 uppercase text-teal-900">Create New Room Type</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-xs font-black uppercase text-gray-400 mb-1">Room Type Name</label>
                    <input type="text" name="name" placeholder="e.g. Standard Room" class="w-full border p-3 rounded-xl font-bold outline-none" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black uppercase text-gray-400 mb-1">Base Price (₱)</label>
                        <input type="number" name="base_price" class="w-full border p-3 rounded-xl font-bold outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-gray-400 mb-1">Bed Type</label>
                        <select name="official_bed_type_id" class="w-full border p-3 rounded-xl font-bold bg-white text-sm" required>
                            <option value="" disabled selected>Select Bed Type</option>
                            <?php foreach($bed_types as $bt): ?>
                                <option value="<?= $bt['bed_type_id'] ?>"><?= htmlspecialchars($bt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase text-gray-400 mb-1">Images (Max 10)</label>
                    <input type="file" name="room_images[]" multiple accept="image/*" class="w-full text-xs font-bold">
                </div>

                <div>
                    <label class="block text-xs font-black uppercase text-gray-400 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full border p-3 rounded-xl font-bold outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase text-gray-400 mb-2">Amenities</label>
                    <div class="grid grid-cols-2 gap-2 h-32 overflow-y-auto p-3 border rounded-xl bg-gray-50">
                        <?php foreach($all_amenities as $amenity): ?>
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700">
                                <input type="checkbox" name="amenities[]" value="<?= $amenity['amenity_id'] ?>">
                                <?= htmlspecialchars($amenity['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-4">
                    <button type="button" onclick="closeAddTypeModal()" class="py-4 bg-gray-100 text-gray-500 rounded-2xl font-black uppercase">Cancel</button>
                    <button type="submit" name="add_room_type" class="py-4 bg-teal-600 text-white rounded-2xl font-black uppercase">Create Type</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>