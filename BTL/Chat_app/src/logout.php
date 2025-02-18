<?php
session_start();
session_destroy();

// Trả về phản hồi JSON thành công
echo json_encode(["status" => "success"]);
exit();
