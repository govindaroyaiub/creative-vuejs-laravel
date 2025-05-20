<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Models\BannerSize;
use App\Models\Preview;
use App\Models\Version;
use App\Models\SubVersion;
use App\Models\SubBanner;
use App\Models\SubVideo;
use App\Models\SubSocial;
use App\Models\SubGif;
use App\Models\ColorPalette;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PreviewApiController extends Controller
{
    public function getAllVersions($id)
    {
        $versions = Version::where('preview_id', $id)->get();
        $activeVersion = Version::where('preview_id', $id)->where('is_active', 1)->first();
        return $data = [
            'versions' => $versions,
            'activeVersion_id' => $activeVersion['id']
        ];
    }

    public function updateActiveVersion($id)
    {
        $version = Version::find($id);
        Version::where('id', $id)->update(['is_active' => 1]);
        $exceptionVersions = Version::select('id')->where('preview_id', $version['preview_id'])->where('id', '!=', $id)->get()->toArray();
        Version::whereIn('id', $exceptionVersions)->where('preview_id', $version['preview_id'])->update(['is_active' => 0]);

        $versions = Version::where('preview_id', $version['preview_id'])->get();

        return $data = [
            'versions' => $versions,
            'activeVersion_id' => $id
        ];
    }

    public function getVersionType($id)
    {
        $version = Version::find($id);
        $subVersions = SubVersion::where('version_id', $id)->get();
        $activeSubVersion = SubVersion::where('version_id', $id)->where('is_active', 1)->first();

        return $data = [
            'type' => $version['type'],
            'subVersions' => $subVersions,
            'version_name' => $version['name'],
            'activeSubVersion' => $activeSubVersion['id'],
            'version_description' => $version['description']
        ];
    }
}
