<?php

namespace App\Http\Controllers;

use App\Models\newVersion;
use App\Models\newFeedbackSet;
use App\Models\newFeedback;
use App\Models\newBanner;
use App\Models\newVideo;
use App\Models\newGif;
use App\Models\newSocial;
use Illuminate\Http\Request;
use App\Models\BannerSize;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Support\SafeZip;
use App\Http\Concerns\AuthorizesPreviewAccess;
use ZipArchive;

class NewBannerController extends Controller
{
    use AuthorizesPreviewAccess;

    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(newBanner $newBanner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(newBanner $newBanner)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, newBanner $newBanner, $id)
    {
        // The previous version of this method had no validate() call,
        // so any extension/MIME could be uploaded and renamed to .zip.
        // Locking it down to actual ZIPs (and capping size) keeps the
        // extractor from being handed arbitrary attacker bytes.
        $request->validate([
            'size_id' => 'nullable|integer|exists:banner_sizes,id',
            'file'    => 'nullable|file|mimes:zip|max:51200', // 50 MB
        ]);

        DB::beginTransaction();
        try {
            $banner = $newBanner->findOrFail($id);
            $this->authorizeBanner($banner);

            // Update name, size, position if provided
            $banner->update([
                'size_id' => $request->input('size_id', $banner->size_id),
                // Add other fields if needed
            ]);

            // If a new file is uploaded, replace the file and update path/size
            if ($request->hasFile('file')) {
                // Delete previous banner folder
                if ($banner->path) {
                    $oldPath = public_path($banner->path);
                    if (is_dir($oldPath)) {
                        File::deleteDirectory($oldPath);
                    }
                }

                $previewName = str_replace(' ', '_', $banner->version->feedbackset->feedback->category->preview->name ?? 'banner');
                $uniqueSuffix = uniqid('_');
                $uploadDir = public_path("uploads/banners");
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $zipName = $previewName . $uniqueSuffix . '.zip';
                $zipPath = $uploadDir . '/' . $zipName;
                $request->file('file')->move($uploadDir, $zipName);

                // Calculate zip size
                $zipSizeBytes = filesize($zipPath);
                $zipSize = $zipSizeBytes < 1048576
                    ? round($zipSizeBytes / 1024, 2) . ' KB'
                    : round($zipSizeBytes / 1048576, 2) . ' MB';

                // Extract zip — SafeZip walks the entry table and
                // refuses any name containing `..`, an absolute path,
                // or a Windows drive prefix (ZIP-slip protection).
                $extractDir = $uploadDir . '/' . $previewName . $uniqueSuffix;
                try {
                    SafeZip::extract($zipPath, $extractDir);
                } finally {
                    if (is_file($zipPath)) {
                        @unlink($zipPath);
                    }
                }

                $banner->update([
                    'name' => $zipName,
                    'path' => "uploads/banners/{$previewName}{$uniqueSuffix}/",
                    'file_size' => $zipSize,
                ]);
            }

            DB::commit();
            return response('', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(newBanner $newBanner, $id)
    {
        DB::beginTransaction();
        try {
            $banner = $newBanner->findOrFail($id);
            $this->authorizeBanner($banner);

            // Delete the folder from the path
            if ($banner->path) {
                $bannerPath = public_path($banner->path);
                if (is_dir($bannerPath)) {
                    File::deleteDirectory($bannerPath);
                }
            }

            // Delete the row
            $banner->delete();

            DB::commit();
            return response('', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response($e->getMessage(), 500);
        }
    }

    /**
     * Public JS tag endpoint. Embedded on third-party sites or in
     * ad platforms (CM360, etc.) via:
     *
     *   <script src="https://APP_URL/tag/banner/{id}.js" async></script>
     *
     * The script injects an iframe sized from `banner_sizes` and pointed
     * at the banner's stored `index.html`. No auth — banner assets at
     * `/uploads/banners/...` are already publicly served, so the tag
     * adds no extra exposure.
     */
    public function tag($id)
    {
        $banner = newBanner::with('size')->find($id);

        if (! $banner || ! $banner->size) {
            return response("/* banner {$id} not found */", 404)
                ->header('Content-Type', 'application/javascript');
        }

        // The tag is a no-op unless an active Orbit embed exists for
        // this banner. Returning empty JS keeps third-party pages
        // clean (no console errors, no rogue iframe) when toggled off.
        $embed = \App\Models\OrbitEmbed::where('banner_id', $banner->id)->first();
        if (! $embed || ! $embed->is_active) {
            return response("/* orbit embed inactive */", 200)
                ->header('Content-Type', 'application/javascript; charset=utf-8')
                ->header('Cache-Control', 'no-store')
                ->header('Access-Control-Allow-Origin', '*');
        }

        // Iframe loads the Orbit wrapper (not the raw index.html) so we
        // can inject a click tracker. The preview page still uses the
        // direct /uploads/... URL, keeping its metrics separate.
        $iframeSrc = route('orbit.serve-banner', ['id' => $banner->id]);
        $width  = (int) $banner->size->width;
        $height = (int) $banner->size->height;

        $trackUrl = route('orbit.track.view', ['id' => $banner->id]);

        $payload = [
            'src'    => $iframeSrc,
            'width'  => $width,
            'height' => $height,
            'track'  => $trackUrl,
        ];

        $js = "(function(){var d=" . json_encode($payload, JSON_UNESCAPED_SLASHES) . ";"
            . "var s=document.currentScript;"
            . "var f=document.createElement('iframe');"
            . "f.src=d.src;f.width=d.width;f.height=d.height;"
            . "f.setAttribute('frameborder','0');f.setAttribute('scrolling','no');"
            . "f.style.cssText='border:0;display:block;width:'+d.width+'px;height:'+d.height+'px;';"
            . "if(s&&s.parentNode){s.parentNode.insertBefore(f,s);}else{document.body.appendChild(f);}"
            . "try{if(navigator.sendBeacon){navigator.sendBeacon(d.track);}"
            . "else{fetch(d.track,{method:'POST',mode:'no-cors',keepalive:true});}}catch(e){}"
            . "})();";

        return response($js, 200)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Cache-Control', 'no-store')
            ->header('Access-Control-Allow-Origin', '*');
    }

    function download($id)
    {
        $banner = newBanner::findOrFail($id);
        $this->authorizeBanner($banner);
        $folderPath = public_path($banner->path);

        // Ensure trailing slash
        if (substr($folderPath, -1) !== DIRECTORY_SEPARATOR) {
            $folderPath .= DIRECTORY_SEPARATOR;
        }

        if (!is_dir($folderPath)) {
            abort(404, 'Banner folder not found.');
        }

        // Create a temporary zip file
        $zipFileName = 'banner_' . basename($folderPath) . '.zip';
        $zipFilePath = public_path($zipFileName);

        $zip = new \ZipArchive;
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folderPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($folderPath));
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
        } else {
            abort(500, 'Could not create ZIP file.');
        }

        // Return the zip as a download and delete after send
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }
}
