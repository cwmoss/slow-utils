<?php

namespace slow\util;

class upload {

    public static function normalize_files_array($files = []) {
        $normalized_array = [];

        foreach ($files as $index => $file) {
            if (!is_array($file['name'])) {
                $normalized_array[$index][] = $file;
                continue;
            }

            foreach ($file['name'] as $idx => $name) {
                $normalized_array[$index][$idx] = [
                    'name' => $name,
                    'type' => $file['type'][$idx],
                    'tmp_name' => $file['tmp_name'][$idx],
                    'error' => $file['error'][$idx],
                    'size' => $file['size'][$idx]
                ];
            }
        }

        return $normalized_array;
    }

    public static function get_mime_type($file) {
        $finfo = finfo_open();
        $mimetype = finfo_file($finfo, $file, \FILEINFO_MIME_TYPE);
        $ext = finfo_file($finfo, $file, \FILEINFO_EXTENSION);
        $fext = explode("/", $ext)[0];
        if ($fext == '???') {
            $fext = "";
        } elseif ($fext == 'jpeg') {
            $fext = "jpg";
        }

        finfo_close($finfo);
        return [$mimetype, $fext, $ext];
    }

    public static function stream_to_file($name) {
        $tmpfname = tempnam(sys_get_temp_dir(), 'sh-');
        #file_put_contents($tmpfname, file_get_contents('php://input'));

        $in_stream = fopen("php://input", "rb");
        $out_stream = fopen($tmpfname, "w+b");
        $ok = stream_copy_to_stream($in_stream, $out_stream);
        fclose($in_stream);
        fclose($out_stream);

        #$mime="";
        $mime = self::get_mime_type($tmpfname);

        $error = 0;
        $size = filesize($tmpfname);

        if (!$size) {
            $error = UPLOAD_ERR_NO_FILE;
        }
        return [
            'name' => $name,
            'type' => 'stream',
            'tmp_name' => $tmpfname,
            'error' => $error,
            'size' => $size,
            'mime' => $mime,
            'extension' => $mime[1]
        ];
    }

    public static function get_image_dimensions($fname, $mime) {
        $valid = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($mime, $valid)) {
            return false;
        }
        $info = getimagesize($fname);
        if (!$info) {
            return false;
        }
        $info['xy'] = $info[0] . 'x' . $info[1];
        return $info;
    }
}
