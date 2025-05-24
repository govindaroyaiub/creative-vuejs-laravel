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
            'activeSubVersion_id' => $activeSubVersion['id'],
            'version_description' => $version['description'],
            'version_id' => $id
        ];
    }

    public function setBannerActiveSubVersion($id)
    {
        $subVersion = SubVersion::find($id);
        $version = Version::where('id', $subVersion['version_id'])->first();
        SubVersion::where('id', $id)->update(['is_active' => 1]);
        $exceptionSubVersions = SubVersion::select('id')->where('version_id', $version['id'])->where('id', '!=', $id)->get()->toArray();
        SubVersion::whereIn('id', $exceptionSubVersions)->where('version_id', $version['id'])->update(['is_active' => 0]);
        $subVersions = SubVersion::where('version_id', $version['id'])->get();

        return $data = [
            'subVersions' => $subVersions,
            'activeSubVersion_id' => $id,
            'version_id' => $subVersion['version_id']
        ];
    }

    public function checkSubVersionCount($id)
    {
        $subVersion = SubVersion::find($id);
        $version = Version::where('id', $subVersion['version_id'])->first();
        return SubVersion::where('version_id', $version['id'])->count();
    }

    public function getActiveSubVersionBannerData($id)
    {
        $banners = SubBanner::join('banner_sizes', 'sub_banners.size_id', '=', 'banner_sizes.id')
            ->where('sub_banners.sub_version_id', $id)
            ->select(
                'sub_banners.*',
                'banner_sizes.width',
                'banner_sizes.height'
            )
            ->orderBy('sub_banners.position')
            ->get();
        return $banners;
    }

    public function changeTheme($preview_id, $color_id)
    {
        // Find the Preview by ID
        $preview = Preview::find($preview_id);

        if (!$preview) {
            return response()->json(['error' => 'Preview not found'], 404);
        }

        // Update the color_palette_id column
        $preview->color_palette_id = $color_id;
        $preview->save();
        return response()->json(['success' => true, 'message' => 'Theme changed successfully']);
    }

    public function getActiveSubVersionSocialData($id)
    {
        $socials = SubSocial::leftJoin('socials', 'sub_socials.social_id', '=', 'socials.id')
        ->where('sub_socials.sub_version_id', $id)
        ->orderBy('sub_socials.position')
        ->select(
            'sub_socials.*',
            'socials.name as social_name'
        )
        ->get();

        return $socials;
    }
}
