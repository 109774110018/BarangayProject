<?php
require_once __DIR__.'/../includes/config.php';
start_resident_session();
header('Content-Type: application/json');
if (!is_resident()) { echo json_encode(['error'=>'unauthorized']); exit; }
$rid=trim($_GET['rid']??'');
$acc=current_resident();
if (!$rid||$rid!==($acc['resident_id']??'')) { echo json_encode(['error'=>'forbidden']); exit; }
$records=db_fetch_all('SELECT record_id,status FROM records WHERE resident_id=? AND (is_deleted IS NULL OR is_deleted=0)',[$rid]);
echo json_encode(['records'=>$records]);
