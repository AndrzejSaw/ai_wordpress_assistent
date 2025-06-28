<?php
// Запретить прямой доступ к директории
header('HTTP/1.0 403 Forbidden');
exit('Direct access is not allowed.');
