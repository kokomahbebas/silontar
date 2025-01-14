<?php

namespace App\Http\Controllers;

use App\Exports\PengajuanExport;
use App\Models\Permohonan;
use App\Models\Berkas;
use App\Models\PermohonanDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function beranda()
    {
        return view('admin.beranda', [
            'pageTitle' => 'SILONTAR | Admin Beranda',
            'page' => 'Beranda',
            'permohonan' => Permohonan::get(),
        ]);
    }
    public function pengajuan()
    {
        $pengajuan = Permohonan::join('user', 'user.user_id', 'permohonan.user_id')->leftJoin('permohonan_detail', 'permohonan.permohonan_id', '=', 'permohonan_detail.permohonan_id')->get(['user.*', 'permohonan.*', 'permohonan_detail.permohonan']);
        // dd($pengajuan);
        return view('admin.pengajuan', [
            'pageTitle' => 'SILONTAR | Admin Pengajuan',
            'page' => 'Pengajuan',
            'pengajuan' => $pengajuan,
        ]);
    }

    public function pengajuanDetail($id)
    {
        $pengajuan = Permohonan::join('user', 'user.user_id', 'permohonan.user_id')->where('permohonan.permohonan_id', $id)->first();
        return view('admin.pengajuan-detail', [
            'pageTitle' => 'SILONTAR | Admin Detail',
            'page' => 'Pengajuan Detail',
            'pengajuan' => $pengajuan,
            'pd' => PermohonanDetail::where('permohonan_id', $id)->first(),
        ]);
    }
    public function pengajuanDetailStore(Request $req, $id)
    {
        date_default_timezone_set("Asia/Makassar");
        $jenis = $req->input('jenis');

        $temp_berkas = $req->file('berkas')->getPathName();
        $file_berkas = $id . '-' . $jenis . time();
        $folder_berkas = "unggah/permohonan-detail/" . $file_berkas . ".pdf";
        move_uploaded_file($temp_berkas, $folder_berkas);
        $berkas = '/unggah/permohonan-detail/' . $file_berkas . '.pdf';

        $data[$jenis] = $berkas;
        $data[$jenis . '_date'] = date('d-m-Y H:i:s');

        if ($req->input('no_surat')) {
            $data[$jenis . '_no'] = $req->input('no_surat');
        }

        PermohonanDetail::where('permohonan_id', $id)->update($data);

        return redirect('/admin/pengajuan/detail/' . $id)->with('success', 'yes');
    }

    public function pengajuanDetailTolak($id)
    {
        date_default_timezone_set("Asia/Makassar");
        PermohonanDetail::create(['permohonan_id' => $id, 'permohonan' => 'Kembalikan Berkas', 'permohonan_date' => date('d-m-Y H:i:s')]);
        return redirect('/admin/pengajuan/detail/' . $id)->with('success', 'yes');
    }
    public function pengajuanDetailTerima($id)
    {
        date_default_timezone_set("Asia/Makassar");
        PermohonanDetail::create(['permohonan_id' => $id, 'permohonan' => 'Setuju', 'permohonan_date' => date('d-m-Y H:i:s')]);
        return redirect('/admin/pengajuan/detail/' . $id)->with('success', 'yes');
    }
    public function pengajuanDetailUpdate(Request $req, $id)
    {
        date_default_timezone_set("Asia/Makassar");
        if ($req->input('permohonan')) {
            PermohonanDetail::where('permohonan_id', $id)->update([$req->input('jenis') => $req->input('permohonan'), 'permohonan_date' => date('d-m-Y H:i:s')]);
        } else if ($req->input('lengkapi_berkas')) {
            PermohonanDetail::where('permohonan_id', $id)->update([$req->input('jenis') => $req->input('lengkapi_berkas'), 'lengkapi_berkas_date' => date('d-m-Y H:i:s')]);
        }
        return redirect('/admin/pengajuan/detail/' . $id)->with('success', 'yes');
    }
    public function user()
    {
        $user = User::where('role', 'user')->get();
        return view('admin.user', [
            'pageTitle' => 'SILONTAR | Admin User',
            'page' => 'User',
            'user' => $user,
        ]);
    }
    public function profil()
    {
        return view('admin.profil', [
            'pageTitle' => 'SILONTAR | Admin Profil',
            'page' => 'Profil',
            'dataUser' => User::find(auth()->user()->user_id),
        ]);
    }

    public function cetak_laporan()
    {
        return view('admin.cetak_laporan', [
            'pageTitle' => 'SILONTAR | Admin Cetak Laporan',
            'page' => 'Cetak Laporan',
        ]);
    }

    public function edituser()
    {
        return view('admin.edituser');
    }
    public function komentarSimpan(Request $req, $id)
    {
        $data = [
            'komentar' => $req->input('komentar'),
        ];
        // MASUKKAN KE DATABASE
        PermohonanDetail::where('permohonan_id', $id)->update($data);

        return redirect('/admin/pengajuan/detail/' . $id);
    }

    public function dokumen()
    {
        $berkas = Berkas::where('berkas.berkas_id', auth()->user()->user_id)->orderBy('berkas.created_at', 'desc')->paginate(10);
        return view('admin.dokumen', [
            'pageTitle' => 'SILONTAR | Admin Dokumen',
            'page' => 'Dokumen',
            'dataUser' => User::find(auth()->user()->user_id),
            'berkas' => $berkas
        ]);
    }

    public function dokumenStore(Request $request)
    {
        $berkas = ['path', 'keterangan', 'permen', 'edaran', 'panduan'];
        // dd($request->file('npwp'));
        foreach ($berkas as $key => $r) {
            $temp_berkas = $request->file($r)->getPathName();
            $file_berkas = '-' . $r . time();
            $folder_berkas = "unggah/berkas-dokumen/" . $file_berkas . ".pdf";
            move_uploaded_file($temp_berkas, $folder_berkas);
            $berkas[$key] = '/unggah/berkas-dokumen/' . $file_berkas . '.pdf';
        }

        $data = [
            'path' => $berkas[0],
            'keterangan' => $berkas[1],
            'permen' => $berkas[2],
            'edaran' => $berkas[3],
            'panduan' => $berkas[4],
            'berkas_id' => auth()->user()->user_id,
        ];

        Berkas::create($data);

        return redirect('/admin/dokumen/')->with('success', 'Berkas berhasil di upload!');
    }


    public function exportExcel(Request $req)
    {
        return Excel::download(new PengajuanExport($req->query('tahun'), $req->query('bulan')), 'Permohonan Detail Bulan ' . $req->query('bulan') . ' Tahun ' . $req->query('tahun') . '.xlsx');
    }
}
