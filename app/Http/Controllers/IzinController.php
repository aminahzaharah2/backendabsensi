<?php

namespace App\Http\Controllers;

use App\Mail\RespondNotification;
use App\Mail\SendNotification;
use App\Models\Izin;
use App\Models\User;
use App\Http\Requests\IzinRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class IzinController extends Controller
{
    public function index($id){
        $result = Izin::where("idUser",$id)->get();
        if($result){
            return response()->json([
                'results'=> $result
            ],200);
        }else{
            return response()->json([
                'message' => "Izin tidak ditemukan"
            ],404);
        }   
    }

    public function DashboardSiswa($id){
        $izinMasuk = Izin::where("typeIzin","Masuk")->where("idUser", $id)->get();
        $izinKeluar = Izin::where("typeIzin","Keluar")->where("idUser", $id)->get();
        $izinPulang = Izin::where("typeIzin","Pulang")->where("idUser", $id)->get();
        $izinDiizinkan = Izin::where("statusPengajuan","Diizinkan")->where("idUser", $id)->get();
        $izinDitolak = Izin::where("statusPengajuan","Ditolak")->where("idUser", $id)->get();
        $izinPending = Izin::where("statusPengajuan","pending")->where("idUser", $id)->get();

        return response()->json([
            "masuk"=>count($izinMasuk),
            "keluar"=>count($izinKeluar),
            "pulang"=>count($izinPulang),
            "diizinkan"=>count($izinDiizinkan),
            "ditolak"=>count($izinDitolak),
            "pending"=>count($izinPending)
        ],200);
    }

    public function DashboardGuru($idGuru){
        $izinMasuk = Izin::where("typeIzin","Masuk")->where("idUser", $idGuru)->get();
        $izinKeluar = Izin::where("typeIzin","Keluar")->where("idUser", $idGuru)->get();
        $izinPulang = Izin::where("typeIzin","Pulang")->where("idUser", $idGuru)->get();
        $izinDiizinkan = Izin::where("statusPengajuan","Diizinkan")->where("idUser", $idGuru)->get();
        $izinDitolak = Izin::where("statusPengajuan","Ditolak")->where("idUser", $idGuru)->get();
        $pengajuanSiswa = Izin::where("guruPengajar", $idGuru)->where("statusPengajuan","pending")->get();
        $totalSiswa = Izin::where("guruPengajar", $idGuru)->get();
        $menungguPersetujuan = Izin::where("idUser", $idGuru)->where("responKurikulum", "pending")->where("statusPengajuan","pending")->get();

        return response()->json([
            "masuk"=>count($izinMasuk),
            "keluar"=>count($izinKeluar),
            "pulang"=>count($izinPulang),
            "diizinkan"=>count($izinDiizinkan),
            "ditolak"=>count($izinDitolak),
            "pengajuanSiswa"=>count($pengajuanSiswa),
            "totalSiswa" =>count($totalSiswa),
            "menungguPersetujuan"=> count($menungguPersetujuan)
        ],200);
    }


    public function getIzinByKurikulumId($id){
        $result = Izin::where('kurikulum', $id)->get();
        if($result){
            return response()->json([
                'results'=> $result
            ],200);
        }else{
            return response()->json([
                'message' => "Izin tidak ditemukan"
            ],404);
        } 
    }

    public function getIzinGuruById($id){
        $result = Izin::where("idUser",$id)->get();
        if($result){
            return response()->json([
                'results'=> $result
            ],200);
        }else{
            return response()->json([
                'message' => "Izin tidak ditemukan"
            ],404);
        } 
    }

    public function getIzinById($id){
        // $resultsIzin = Izin::find($id);
        $resultsIzin = Izin::where('id', $id)->get();
        if($resultsIzin){
            return response()->json([
                'results'=> $resultsIzin
            ],200);
        }else{
            return response()->json([
                'message' => "Izin tidak ditemukan"
            ],404);
        } 
    }

    public function Batalkan($id, $role){
        $getRecord = Izin::find($id);

        if(!$getRecord){
            return response()->json([
                'message' => "Data Tidak Ditemukan"
            ],404);
        }

        $getRecord->statusPengajuan = "Batalkan";
        if($role == 1){
            $getRecord->responGuruPengajar = "Dibatalkan";
        }else if($role == 2){
            $getRecord->responKurikulum = "Dibatalkan";
        }

        $getRecord->save();
        return response()->json([
            'message'=> "Data berhasil diupdate"
        ],200);
    }

    
    public function AllPermissionGuruPengajar($id){
        $results = Izin::where('guruPengajar', $id)->get();
        if($results){
            return response()->json([
                'results'=> $results
            ],200);
        }else{
            return response()->json([
                'message' => "Izin tidak ditemukan"
            ],404);
        } 
    }

    public function AllPermissionGuruPiket($id){
        $results = Izin::where('guruPiket', $id)->get();
        if($results){
            return response()->json([
                'results'=> $results
            ],200);
        }else{
            return response()->json([
                'message' => "Izin tidak ditemukan"
            ],404);
        } 
    }

    public function EditIzin(IzinRequest $request, $id){
        $getIzinGuru = Izin::find($id);
        if($getIzinGuru){
            try{
            if($request->typeIzin == 'Masuk'){
                $validator = Validator::make($request->all(),[
                    'idUser' => 'required',
                    'idMapel' => 'required',
                    'kelas' => 'required|string',
                    'guruPengajar' => 'required',
                    'keterangan'=> 'required',
                    'jamMasuk' => [
                        'required',
                        'date_format:H:i', 
                        'after:08:00',     
                        'before:16:00'
                    ],
                    'typeIzin'=> 'required',
                ],
            [
                'idUser.required' => 'wajib diisi',
                'idMapel.required' => 'wajib diisi',
                'kelas.required'=> 'wajib diisi',
                'guruPengajar.required' => 'wajib diisi',
                'keterangan.required'=> 'wajib diisi',
                'jamMasuk.required'=> 'wajib diisi',
            ]
        );
            }else if($request->typeIzin == 'Keluar'){
                $validator = Validator::make($request->all(),[
                    'idUser' => 'required',
                    'idMapel' => 'required',
                    'kelas' => 'required|string',
                    'guruPengajar' => 'required',
                    'jamMasuk' => [
                        'required',
                        'date_format:H:i', 
                        'after:08:00',     
                        'before:16:00'
                    ],
                    'jamKeluar' => [
                        'required',
                        'date_format:H:i', 
                        'after:08:00',     
                        'before:16:00'
                    ],
                    'keterangan'=> 'required',
                    'typeIzin'=> 'required',
                ],
                [
                    'idUser.required' => 'wajib diisi',
                    'idMapel.required' => 'wajib diisi',
                    'kelas.required'=> 'wajib diisi',
                    'guruPengajar.required' => 'wajib diisi',
                    'jamMasuk.required'=> 'wajib diisi',
                    'jamKeluar.required'=> 'wajib diisi',
                    'keterangan.required'=> 'wajib diisi',
                ]
            );

            }else if($request->typeIzin == 'Pulang'){
                $validator = Validator::make($request->all(),[
                    'idUser' => 'required',
                    'idMapel' => 'required',
                    'kelas' => 'required|string',
                    'guruPengajar' => 'required',
                    'jamKeluar' => [
                        'required',
                        'date_format:H:i', 
                        'after:08:00',     
                        'before:16:00' 
                    ],
                    'keterangan'=> 'required',
                    'typeIzin'=> 'required',
                ],
                [
                    'idUser.required' => 'wajib diisi',
                    'idMapel.required' => 'wajib diisi',
                    'kelas.required'=> 'wajib diisi',
                    'guruPengajar.required' => 'wajib diisi',
                    'keterangan.required'=> 'wajib diisi',
                    'jamMasuk.required'=> 'wajib diisi',
                ]
            );
            }

            if($validator->fails()){
                return response()->json([
                    'error'=> $validator->errors()
                ],422);
            }

            if($request->foto != null){
            $getIzinGuru->foto = $request->foto;
            }

            if($request->typeIzin == "Masuk"){
                $getIzinGuru->foto = $request->foto;
                $getIzinGuru->jamKeluar = null;
                $getIzinGuru->jamMasuk = $request->jamMasuk;
            }else if($request->typeIzin == "Keluar"){
                $getIzinGuru->foto = null;
                $getIzinGuru->jamKeluar = $request->jamKeluar;
                $getIzinGuru->jamMasuk = $request->jamMasuk;
            }else{
                $getIzinGuru->foto = null;
                $getIzinGuru->jamKeluar = $request->jamKeluar;
                $getIzinGuru->jamMasuk = null;
            }

            $getIzinGuru->idMapel = $request->idMapel;
            $getIzinGuru->guruPengajar = $request->guruPengajar;
            $getIzinGuru->keterangan = $request->keterangan;
            $getIzinGuru->typeIzin = $request->typeIzin;

            $getIzinGuru->save();
            return response()->json([
                'message'=> "Data berhasil diupdate"
            ],200);

            }catch(\Exception $e){
                return response()->json([
                    'message'=>$e
                ],505);
            }

        }
        return response()->json([
            'message' => "Izin Tidak Ditemukan"
        ],404);
    }

    public function tambahIzin(IzinRequest $request){
        try{
            if($request->typeIzin == 'Masuk'){
                $validator = Validator::make($request->all(),[
                    'idUser' => 'required',
                    'idMapel' => 'required',
                    'kelas' => 'required|string',
                    'guruPengajar' => 'required|string',
                    'keterangan'=> 'required',
                    'jamMasuk' =>['required',
                    'date_format:H:i', 
                    'after:08:00',     
                    'before:16:00'
                ],
                    'typeIzin'=> 'required',
                ],
                [
                    'idUser.required' => 'wajib diisi',
                    'idMapel.required' => 'wajib diisi',
                    'kelas.required'=> 'wajib diisi',
                    'guruPengajar.required' => 'wajib diisi',
                    'keterangan.required'=> 'wajib diisi',
                    'jamMasuk.required'=> 'wajib diisi',
                ]
            );
            }else if($request->typeIzin == 'Keluar'){
                $validator = Validator::make($request->all(),[
                    'idUser' => 'required',
                    'idMapel' => 'required',
                    'kelas' => 'required|string',
                    'guruPengajar' => 'required|string',
                    'jamKeluar'=> ['required',                    
                    'date_format:H:i', 
                    'after:08:00',     
                    'before:16:00'
                ],
                    'jamMasuk'=> ['required',                    
                    'date_format:H:i', 
                    'after:08:00',     
                    'before:16:00'
                ],
                    'keterangan'=> 'required',
                    'typeIzin'=> 'required',
                ],
                [
                    'idUser.required' => 'wajib diisi',
                    'idMapel.required' => 'wajib diisi',
                    'kelas.required'=> 'wajib diisi',
                    'guruPengajar.required' => 'wajib diisi',
                    'keterangan.required'=> 'wajib diisi',
                    'jamMasuk.required'=> 'wajib diisi',
                    'jamKeluar.required'=> 'wajib diisi',
                ]
            );
            }else if($request->typeIzin == 'Pulang'){
                $validator = Validator::make($request->all(),[
                    'idUser' => 'required',
                    'idMapel' => 'required',
                    'kelas' => 'required|string',
                    'guruPengajar' => 'required|string',
         
                    'jamKeluar'=> ['required',
                    'date_format:H:i', 
                    'after:08:00',     
                    'before:16:00'
                ],
                    'keterangan'=> 'required',
                    'typeIzin'=> 'required',
            ],
            [
                
                    'idUser.required' => 'wajib diisi',
                    'idMapel.required' => 'wajib diisi',
                    'kelas.required'=> 'wajib diisi',
                    'guruPengajar.required' => 'wajib diisi',
                    
                    'keterangan.required'=> 'wajib diisi',
                    'jamKeluar.required'=> 'wajib diisi',
            ]
            );
            }

            if($validator->fails()){
                return response()->json([
                    'error'=> $validator->errors()
                ],422);
            }

            if($request->foto != null){
                $add = Izin::create([
                    'idUser' => $request->idUser,
                    'idMapel'=> $request->idMapel,
                    'kelas'=> $request->kelas,
                    'foto' => $request->foto,
                    'guruPengajar'=> $request->guruPengajar,
                    'jamKeluar'=> $request->jamKeluar,
                    'jamMasuk'=> $request->jamMasuk,
                    'keterangan'=> $request->keterangan,
                    'typeIzin'=> $request->typeIzin,
                    'responGuruPengajar' => "pending",
                    'responKurikulum' => "pending",
                ]);
            }else{
                $add = Izin::create([
                    'idUser' => $request->idUser,
                    'idMapel'=> $request->idMapel,
                    'kelas'=> $request->kelas,
                    'guruPengajar'=> $request->guruPengajar,
                    'jamKeluar'=> $request->jamKeluar,
                    'jamMasuk'=> $request->jamMasuk,
                    'keterangan'=> $request->keterangan,
                    'typeIzin'=> $request->typeIzin,
                    'responGuruPengajar' => "pending",
                    'responKurikulum' => "pending",
                ]);
            }

            $findGuru = User::find($request->guruPengajar);

            if($add){
                try{
                    
                    $findGuru['link'] = "http://localhost:5173/Detail/".$add->id;
                    // $findKurikulum['link'] = "http://localhost:5173/Detail/".$add->id;
                    $findGuru['pengaju'] = "Siswa";
                    // $findKurikulum['pengaju'] = "Siswa";

                    Mail::mailer('smtp')->to($findGuru->email)->send(new SendNotification($findGuru));
                    // Mail::mailer('smtp')->to($findKurikulum->email)->send(new SendNotification($findKurikulum));

                    return response()->json([
                        "message" => "Izin Berhasil Diajukan",
                        "guru"=> $findGuru->email,
                    ],200);
                }catch(\Exception $e){

                    return response()->json([
                        'message' => $e,
                        "guru"=> $findGuru->email,
                    ],500);
                }
            }
        }catch(\Exception $e){
            return response()->json([
                'message' => $e
            ],500);
        }
    }

        public function tambahIzinGuru(IzinRequest $request){
        try{
            if($request->typeIzin == 'Masuk'){
                $validator = Validator::make($request->all(),[
                    'idUser' => 'required',
                    'kurikulum' => 'required',
                    'keterangan'=> 'required',
                    'jamMasuk' => ['required',
                    'date_format:H:i', 
                    'after:08:00',     
                    'before:16:00'
                ],
                    'typeIzin'=> 'required',
                ],[
                    'idUser.required' => 'wajib diisi',
                    'kurikulum.required' => 'wajib diisi',
                    'keterangan.required'=> 'wajib diisi',
                    'jamMasuk.required'=> 'wajib diisi',
                ]);
            }else if($request->typeIzin == 'Keluar'){
                $validator = Validator::make($request->all(),[
                    'idUser' => 'required',
                    'kurikulum' => 'required',
                    'jamKeluar'=> ['required',
                    'date_format:H:i', 
                    'after:08:00',     
                    'before:16:00'
                ],
                    'jamMasuk'=> ['required',                    
                    'date_format:H:i', 
                    'after:08:00',     
                    'before:16:00'
                ],
                    'keterangan'=> 'required',
                    'typeIzin'=> 'required',
                ],[
                    'idUser.required' => 'wajib diisi',
                    'kurikulum.required' => 'wajib diisi',
                    'jamMasuk.required'=> 'wajib diisi',
                    'jamKeluar.required'=> 'wajib diisi',
                    'keterangan.required'=> 'wajib diisi',
                ]);
            }else if($request->typeIzin == 'Pulang'){
                $validator = Validator::make($request->all(),[
                    'idUser' => 'required',
                    'kurikulum' => 'required',
                    'jamKeluar'=> ['required',
                    'date_format:H:i', 
                    'after:08:00',     
                    'before:16:00'
                ],
                    'keterangan'=> 'required',
                    'typeIzin'=> 'required',
                ],[
                    'idUser.required' => 'wajib diisi',
                    'kurikulum.required' => 'wajib diisi',
                    'jamKeluar.required'=> 'wajib diisi',
                    'keterangan.required'=> 'wajib diisi',
                ]);
            }

            if($validator->fails()){
                return response()->json([
                    'error'=> $validator->errors()
                ],422);
            }

            if($request->foto != null){
                $add = Izin::create([
                    'idUser' => $request->idUser,
                    'foto' => $request->foto,
                    'kurikulum'=> $request->kurikulum,
                    'jamKeluar'=> $request->jamKeluar,
                    'jamMasuk'=> $request->jamMasuk,
                    'keterangan'=> $request->keterangan,
                    'typeIzin'=> $request->typeIzin,
                    'responKurikulum' => "pending",
                ]);
            }else{
                $add = Izin::create([
                    'idUser' => $request->idUser,
                    'kurikulum'=> $request->kurikulum,
                    'jamKeluar'=> $request->jamKeluar,
                    'jamMasuk'=> $request->jamMasuk,
                    'keterangan'=> $request->keterangan,
                    'typeIzin'=> $request->typeIzin,
                    'responKurikulum' => "pending",
                ]);
            }

            $findKurikulum = User::find($request->kurikulum);

            if($add){
                try{
                    
                    $findKurikulum['link'] = "http://localhost:5173/Detail/".$add->id;
                    $findKurikulum['pengaju'] = "Guru";

                    Mail::mailer('smtp')->to($findKurikulum->email)->send(new SendNotification($findKurikulum));

                    return response()->json([
                        "message" => "Izin Berhasil Diajukan",
                        "kurikulum"=> $findKurikulum->email
                    ],200);
                }catch(\Exception $e){

                    return response()->json([
                        'message' => $e,
                        "kurikulum"=> $findKurikulum->email
                    ],500);
                }
            }
        }catch(\Exception $e){
            return response()->json([
                'message' => $e
            ],500);
        }
    }

    public function EditIzinGuru($id, IzinRequest $request){
        $getIzinGuru = Izin::find($id);

            try{
                if(!$getIzinGuru){
                    return response()->json([
                        "message" => "Data Tidak Ditemukan"
                    ],404);
                }
        
                if($request->typeIzin == 'Masuk'){
                    $validator = Validator::make($request->all(),[
                        'idUser' =>'required',
                        'kurikulum' =>'required',
                        'jamMasuk' => ['required',
                        'date_format:H:i', 
                        'after:08:00',     
                        'before:16:00'
                    ],
                        'keterangan'=> 'required',
                        'typeIzin'=> 'required',
                    ],[
                        'idUser.required' => 'wajib diisi',
                        'kurikulum.required' => 'wajib diisi',
                        'jamMasuk.required'=> 'wajib diisi',
                        'keterangan.required'=> 'wajib diisi',
                    ]);
                }else if($request->typeIzin == 'Keluar'){
                    $validator = Validator::make($request->all(),[
                        'idUser' =>'required',
                        'kurikulum' =>'required',
                        'jamKeluar'=> ['required',
                        'date_format:H:i', 
                        'after:08:00',     
                        'before:16:00'
                    ],
                        'jamMasuk'=> ['required',
                        'date_format:H:i', 
                        'after:08:00',     
                        'before:16:00'
                    ],
                        'keterangan'=> 'required',
                        'typeIzin'=> 'required',
                    ],[
                        'idUser.required' => 'wajib diisi',
                        'kurikulum.required' => 'wajib diisi',
                        'jamMasuk.required'=> 'wajib diisi',
                        'jamKeluar.required'=> 'wajib diisi',
                        'keterangan.required'=> 'wajib diisi',
                    ]);
                }else if($request->typeIzin == 'Pulang'){
                    $validator = Validator::make($request->all(),[
                        'idUser' =>'required',
                        'kurikulum' =>'required',
                        'jamKeluar'=> ['required',
                        'date_format:H:i', 
                        'after:08:00',     
                        'before:16:00'
                    ],
                        'keterangan'=> 'required',
                        'typeIzin'=> 'required',
                    ],[
                        'idUser.required' => 'wajib diisi',
                        'kurikulum.required' => 'wajib diisi',
                        'jamKeluar.required'=> 'wajib diisi',
                        'keterangan.required'=> 'wajib diisi',
                    ]);
                }

                if($validator->fails()){
                    return response()->json([
                        'error'=> $validator->errors()
                    ],422);
                }
        
                if($request->foto != null){
                    $getIzinGuru->foto = $request->foto;
                    }
        
                    if($request->typeIzin == "Masuk"){
                        $getIzinGuru->foto = $request->foto;
                        $getIzinGuru->jamKeluar = null;
                        $getIzinGuru->jamMasuk = $request->jamMasuk;
                    }else if($request->typeIzin == "Keluar"){
                        $getIzinGuru->foto = null;
                        $getIzinGuru->jamKeluar = $request->jamKeluar;
                        $getIzinGuru->jamMasuk = $request->jamMasuk;
                    }else{
                        $getIzinGuru->foto = null;
                        $getIzinGuru->jamKeluar = $request->jamKeluar;
                        $getIzinGuru->jamMasuk = null;
                    }
        
                    $getIzinGuru->kurikulum = $request->kurikulum;
                    $getIzinGuru->keterangan = $request->keterangan;
                    $getIzinGuru->typeIzin = $request->typeIzin;
        
                    $getIzinGuru->save();

                return response()->json([
                    'message'=> "Data berhasil diupdate"
                ],200);
    
        
            }catch(\Exception $e){
                return response()->json([
                    'message'=>$e
                ],505);
            }
    }

    public function BeriIzin($id,$role){
        $getRecord = Izin::find($id);

        if(!$getRecord){
            return response()->json([
                'message' => "Data Tidak Ditemukan"
            ],404);
        }

        $findPengaju = User::find($getRecord->idUser);

        if($role == 2){
            $findPengaju['link'] = "http://localhost:5173/Detail/".$id;
            $findPengaju['perespon'] = "Guru";
            $findPengaju['respon'] = "Diizinkan";

            $getRecord->responGuruPengajar = "Diizinkan";

        }else if($role == 5){
            $findPengaju['link'] = "http://localhost:5173/Detail/".$id;
            $findPengaju['perespon'] = "Kurikulum";
            $findPengaju['respon'] = "Diizinkan";

            $getRecord->responKurikulum = "Diizinkan";       
        }
        $getRecord->statusPengajuan = "Diizinkan";  
        Mail::mailer('smtp')->to($findPengaju->email)->send(new RespondNotification($findPengaju));

        $getRecord->save();
        return response()->json([
            'message'=> "Data berhasil diupdate"
        ],200);
    }

    public function Tolak($id,$role){
        try {
            $getRecord = Izin::find($id);

            if(!$getRecord){
                return response()->json([
                    'message' => "Data Tidak Ditemukan"
                ],404);
            }
    
            $findPengaju = User::find($getRecord->idUser);
            if($role == 2){
                $findPengaju['link'] = "http://localhost:5173/Detail/".$id;
                $findPengaju['perespon'] = "Guru";
                $findPengaju['respon'] = "Ditolak";
    
                $getRecord->responGuruPengajar = "Ditolak";    
    
            }else if($role == 5){
                $findPengaju['link'] = "http://localhost:5173/Detail/".$id;
                $findPengaju['perespon'] = "Kurikulum";
                $findPengaju['respon'] = "Ditolak";
    
                $getRecord->responKurikulum = "Ditolak";
            }
            $getRecord->statusPengajuan = "Ditolak";  

    
            Mail::mailer('smtp')->to($findPengaju->email)->send(new RespondNotification($findPengaju));
    
            $getRecord->save();
            return response()->json([
                'message'=> "Data berhasil diupdate",
                'role' => $role,
            ],200);
        } catch(\Exception $e){
            return response()->json([
                'message'=>$e
            ],500);
        }
    }
}