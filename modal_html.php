<!-- View Bahan Baku Modal -->
<div id="viewBahanBakuModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-medium text-gray-900" id="view_modal_title">Detail Bahan Baku</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('viewBahanBakuModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Status Info Banner -->
            <div id="view_status_banner" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4 mt-4 hidden">
                <div class="flex items-start">
                    <div class="text-yellow-600 mr-3">
                        <i class="fas fa-info-circle text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-yellow-800 font-medium mb-1" id="view_banner_title">Informasi Bahan Baku</h4>
                        <p class="text-sm text-yellow-700" id="view_banner_text">
                            Detail bahan baku yang telah diproses.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 mt-4">
                <!-- Informasi Bahan Baku -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <h4 class="text-gray-700 font-medium mb-3 border-b pb-2">Informasi Bahan Baku</h4>
                    <div class="space-y-2">
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Nama Barang</div>
                            <div class="text-sm font-medium" id="view_nama_barang">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Jumlah Total</div>
                            <div class="text-sm font-medium" id="view_qty">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Periode</div>
                            <div class="text-sm font-medium" id="view_periode">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Harga Satuan</div>
                            <div class="text-sm font-medium" id="view_harga_satuan">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Total</div>
                            <div class="text-sm font-medium" id="view_total">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Lokasi</div>
                            <div class="text-sm font-medium" id="view_lokasi">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Tanggal Input</div>
                            <div class="text-sm font-medium" id="view_tanggal_input">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Status</div>
                            <div class="text-sm" id="view_status_container">
                                <span id="view_status" class="px-2 py-1 rounded-full text-xs font-medium">-</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Input Oleh</div>
                            <div class="text-sm font-medium" id="view_input_oleh">-</div>
                        </div>
                    </div>
                </div>
                
                <!-- Informasi Retur/Approved -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" id="view_detail_container">
                    <h4 class="text-gray-700 font-medium mb-3 border-b pb-2" id="view_detail_title">Informasi Retur</h4>
                    <div class="space-y-2" id="view_retur_info">
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Tanggal Retur</div>
                            <div class="text-sm font-medium" id="view_tanggal_retur">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Jumlah Diretur</div>
                            <div class="text-sm font-medium text-red-600" id="view_jumlah_retur">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Jumlah Masuk Stok</div>
                            <div class="text-sm font-medium text-green-600" id="view_jumlah_masuk">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Alasan Retur</div>
                            <div class="text-sm" id="view_catatan_retur">-</div>
                        </div>
                    </div>
                    
                    <div class="space-y-2 hidden" id="view_approved_info">
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Tanggal Approval</div>
                            <div class="text-sm font-medium" id="view_tanggal_approved">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Jumlah Masuk Stok</div>
                            <div class="text-sm font-medium text-green-600" id="view_jumlah_approved">-</div>
                        </div>
                        <div class="grid grid-cols-2">
                            <div class="text-sm text-gray-500">Status Laporan</div>
                            <div class="text-sm font-medium" id="view_status_laporan">-</div>
                        </div>
                    </div>
                    
                    <div class="space-y-2 hidden" id="view_pending_info">
                        <div class="p-3 bg-yellow-50 rounded-lg">
                            <p class="text-sm text-yellow-700">
                                <i class="fas fa-info-circle mr-2"></i>
                                Bahan baku ini masih dalam status pending. Silakan verifikasi untuk memasukkan ke stok.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ringkasan Biaya -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6" id="view_biaya_container">
                <h4 class="text-gray-700 font-medium mb-3 border-b pb-2">Ringkasan Biaya</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="text-sm text-gray-500">Total Nilai Awal</div>
                        <div class="text-lg font-semibold" id="view_nilai_awal">-</div>
                        <div class="text-xs text-gray-500 mt-1" id="view_nilai_awal_detail">-</div>
                    </div>
                    
                    <div class="bg-red-50 p-3 rounded-lg" id="view_nilai_retur_container">
                        <div class="text-sm text-red-500">Nilai Diretur</div>
                        <div class="text-lg font-semibold text-red-600" id="view_nilai_retur">-</div>
                        <div class="text-xs text-red-500 mt-1" id="view_nilai_retur_detail">-</div>
                    </div>
                    
                    <div class="bg-green-50 p-3 rounded-lg" id="view_nilai_masuk_container">
                        <div class="text-sm text-green-500">Nilai Masuk Stok</div>
                        <div class="text-lg font-semibold text-green-600" id="view_nilai_masuk">-</div>
                        <div class="text-xs text-green-500 mt-1" id="view_nilai_masuk_detail">-</div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="button" class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="closeModal('viewBahanBakuModal')">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
</script> 