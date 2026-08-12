@extends('plain')
@section('content')
    <div x-data="serviceReport()" class="min-h-screen bg-transparent-bg">
        <!-- Geometric Header -->
        <div class="bg-primary text-white relative overflow-hidden">
            <!-- Geometric shapes background -->
            <div class="absolute inset-0">
                <div
                    class="absolute top-0 right-0 w-32 h-32 bg-secondary opacity-20 transform rotate-45 translate-x-16 -translate-y-16">
                </div>
                <div
                    class="absolute bottom-0 left-0 w-24 h-24 bg-accent opacity-30 transform rotate-12 -translate-x-12 translate-y-12">
                </div>
                <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-background opacity-10 transform rotate-45"></div>
            </div>

            <div class="relative max-w-md mx-auto px-6 py-8">
                <div class="text-center">
                    <h1 class="text-2xl font-bold tracking-wide">ECOFORPEST</h1>
                    <div class="mt-2 inline-block px-4 py-1 bg-secondary text-primary text-sm font-medium">
                        SERVICE REPORT
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-md mx-auto px-4 py-6 space-y-6">

            <!-- Success Message -->
            @if (session('success'))
                <div x-show="true" x-transition
                    class="geometric-card bg-secondary text-primary px-6 py-4 relative overflow-hidden">
                    <div
                        class="absolute top-0 right-0 w-8 h-8 bg-primary opacity-20 transform rotate-45 translate-x-4 -translate-y-4">
                    </div>
                    <div class="relative flex items-center">
                        <div class="w-6 h-6 bg-primary mr-3 flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs"></i>
                        </div>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <!-- Service Details Card -->
            <div class="bg-white geometric-card relative overflow-hidden shadow-lg">
                <!-- Geometric header -->
                <div class="bg-primary text-white px-6 py-4 geometric-header relative">
                    <div
                        class="absolute top-0 right-0 w-12 h-12 bg-secondary opacity-30 transform rotate-45 translate-x-6 -translate-y-6">
                    </div>
                    <h2 class="relative font-bold text-lg tracking-wide flex items-center">
                        <div class="w-6 h-6 bg-secondary mr-3 transform rotate-45 flex items-center justify-center">
                            <div class="w-3 h-3 bg-primary"></div>
                        </div>
                        DETAIL LAYANAN
                    </h2>
                </div>

                <!-- Content with geometric dividers -->
                <div class="p-6 space-y-5">
                    <div class="flex justify-between items-start relative">
                        <span class="text-gray-600 font-medium z-10">Work Order</span>
                        <span
                            class="font-bold text-primary bg-secondary bg-opacity-20 px-3 py-1">#{{ $report->work_order_number }}</span>
                    </div>
                    <div class="h-px bg-secondary opacity-30"></div>

                    <div class="flex justify-between items-start relative">
                        <span class="text-gray-600 font-medium z-10">Customer</span>
                        <span class="font-bold text-primary text-right max-w-48">{{ $report->customer_name }}</span>
                    </div>
                    <div class="h-px bg-secondary opacity-30"></div>

                    <div class="flex justify-between items-start relative">
                        <span class="text-gray-600 font-medium z-10">Teknisi</span>
                        <span class="font-bold text-primary text-right max-w-48">{{ $report->technician_name }}</span>
                    </div>
                    <div class="h-px bg-secondary opacity-30"></div>

                    <div class="flex justify-between items-center relative">
                        <span class="text-gray-600 font-medium z-10">Status</span>
                        <div class="relative">
                            <div
                                class="geometric-card px-4 py-2 {{ $report->client_approve ? 'bg-secondary text-primary' : 'bg-yellow-400 text-yellow-900' }}">
                                <div
                                    class="absolute top-0 right-0 w-3 h-3 {{ $report->client_approve ? 'bg-primary' : 'bg-yellow-600' }} opacity-40 transform rotate-45 translate-x-1 -translate-y-1">
                                </div>
                                <span class="relative font-bold text-sm">
                                    {{ $report->client_approve ? 'DITANDATANGANI' : 'MENUNGGU' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="h-px bg-secondary opacity-30"></div>

                    <div class="flex justify-between items-start relative">
                        <span class="text-gray-600 font-medium z-10">Tanggal Selesai</span>
                        <span class="font-bold text-primary">{{ $report->close_order }}</span>
                    </div>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="space-y-6">
                <!-- Customer Signature -->
                <div class="bg-white geometric-card relative overflow-hidden shadow-lg">
                    <!-- Geometric header -->
                    <div class="bg-secondary text-primary px-6 py-4 geometric-header relative">
                        <div
                            class="absolute top-0 right-0 w-12 h-12 bg-primary opacity-20 transform rotate-45 translate-x-6 -translate-y-6">
                        </div>
                        <h3 class="relative font-bold text-lg tracking-wide flex items-center">
                            <div class="w-6 h-6 bg-primary mr-3 transform rotate-45 flex items-center justify-center">
                                <div class="w-3 h-3 bg-secondary"></div>
                            </div>
                            TANDA TANGAN CUSTOMER
                        </h3>
                    </div>

                    <div class="p-6">
                        @if (!$report->client_approve)
                            <form method="POST" action="{{ url('/service-report/' . $report->signature_token . '/sign') }}"
                                @submit="submitForm" enctype="multipart/form-data">
                                @csrf
                                <div class="space-y-6">
                                    <div class="relative">
                                        <label class="block text-primary font-bold mb-3 tracking-wide">NAMA CUSTOMER</label>
                                        <div class="geometric-card bg-gray-50 relative overflow-hidden">
                                            <input type="text" name="client_signature_name"
                                                class="w-full px-4 py-3 bg-transparent border-0 focus:outline-none font-medium text-primary"
                                                value="{{ $report->customer_name }}" readonly required>
                                            <div
                                                class="absolute top-0 right-0 w-6 h-6 bg-secondary opacity-30 transform rotate-45 translate-x-3 -translate-y-3">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="relative">
                                        <label class="block text-primary font-bold mb-3 tracking-wide">TANDA TANGAN
                                            DIGITAL</label>
                                        <div class="text-center relative">
                                            <canvas x-ref="signatureCanvas"
                                                class="bg-background bg-opacity-10 mx-auto cursor-crosshair w-full border border-secondary"
                                                @mousedown="startDrawing($event)" @mousemove="draw($event)"
                                                @mouseup="stopDrawing()" @mouseout="stopDrawing()"
                                                @touchstart="startDrawing($event)" @touchmove="draw($event)"
                                                @touchend="stopDrawing()" @touchcancel="stopDrawing()"></canvas>
                                            <input type="hidden" name="client_signature" x-ref="signatureInput">
                                        </div>

                                        <div class="flex justify-center mt-4">
                                            <div class="relative">
                                                <button type="button" @click="clearSignature()"
                                                    class="geometric-card px-6 py-2 bg-gray-400 text-white font-bold tracking-wide hover:bg-gray-500 transition-colors relative overflow-hidden">
                                                    <div
                                                        class="absolute top-0 right-0 w-4 h-4 bg-gray-600 opacity-40 transform rotate-45 translate-x-2 -translate-y-2">
                                                    </div>
                                                    <span class="relative">BERSIHKAN</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="relative">
                                        <button type="submit" :disabled="!hasSignature"
                                            class="w-full geometric-card py-4 bg-primary text-white font-bold text-lg tracking-wide hover:bg-opacity-90 transition-all disabled:bg-gray-300 disabled:cursor-not-allowed relative overflow-hidden">
                                            <div
                                                class="absolute top-0 right-0 w-12 h-12 bg-secondary opacity-30 transform rotate-45 translate-x-6 -translate-y-6">
                                            </div>
                                            <div
                                                class="absolute bottom-0 left-0 w-8 h-8 bg-background opacity-20 transform rotate-45 -translate-x-4 translate-y-4">
                                            </div>
                                            <span class="relative">KIRIM TANDA TANGAN</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <div class="text-center relative">
                                <div
                                    class="geometric-card bg-background bg-opacity-30 p-6 mx-auto max-w-xs relative overflow-hidden">
                                    <div
                                        class="absolute top-0 right-0 w-8 h-8 bg-secondary opacity-40 transform rotate-45 translate-x-4 -translate-y-4">
                                    </div>
                                    <img src="{{ asset('storage/' . $report->client_signature) }}"
                                        alt="Tanda Tangan Customer"
                                        class="max-w-full h-auto max-h-32 mx-auto relative z-10">
                                </div>
                                <p class="mt-4 font-bold text-primary text-lg">{{ $report->customer_name }}</p>
                                <div
                                    class="mt-3 inline-block geometric-card px-6 py-2 bg-secondary text-primary relative overflow-hidden">
                                    <div
                                        class="absolute top-0 right-0 w-4 h-4 bg-primary opacity-30 transform rotate-45 translate-x-2 -translate-y-2">
                                    </div>
                                    <span class="relative font-bold tracking-wide">TELAH DITANDATANGANI</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Technician Signature -->
                <div class="bg-white geometric-card relative overflow-hidden shadow-lg">
                    <!-- Geometric header -->
                    <div class="bg-primary text-white px-6 py-4 geometric-header relative">
                        <div
                            class="absolute top-0 right-0 w-12 h-12 bg-secondary opacity-30 transform rotate-45 translate-x-6 -translate-y-6">
                        </div>
                        <h3 class="relative font-bold text-lg tracking-wide flex items-center">
                            <div class="w-6 h-6 bg-secondary mr-3 transform rotate-45 flex items-center justify-center">
                                <div class="w-3 h-3 bg-primary"></div>
                            </div>
                            TANDA TANGAN TEKNISI
                        </h3>
                    </div>

                    <div class="p-6">
                        @if (!$report->technician_approve)
                            <form method="POST"
                                action="{{ url('/service-report/' . $report->signature_token . '/sign-technician') }}"
                                @submit="submitTechnicianForm" enctype="multipart/form-data">
                                @csrf
                                <div class="space-y-6">
                                    <div class="relative">
                                        <label class="block text-primary font-bold mb-3 tracking-wide">NAMA TEKNISI</label>
                                        <div class="geometric-card bg-gray-50 relative overflow-hidden">
                                            <input type="text" name="technician_signature_name"
                                                class="w-full px-4 py-3 bg-transparent border-0 focus:outline-none font-medium text-primary"
                                                value="{{ $report->technician_name }}" readonly required>
                                            <div
                                                class="absolute top-0 right-0 w-6 h-6 bg-secondary opacity-30 transform rotate-45 translate-x-3 -translate-y-3">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="relative">
                                        <label class="block text-primary font-bold mb-3 tracking-wide">TANDA TANGAN
                                            DIGITAL</label>
                                        <div class="text-center relative">
                                            <canvas x-ref="technicianSignatureCanvas"
                                                class="bg-background bg-opacity-10 mx-auto cursor-crosshair w-full border border-secondary"
                                                @mousedown="startDrawingTechnician($event)"
                                                @mousemove="drawTechnician($event)" @mouseup="stopDrawingTechnician()"
                                                @mouseout="stopDrawingTechnician()"
                                                @touchstart="startDrawingTechnician($event)"
                                                @touchmove="drawTechnician($event)" @touchend="stopDrawingTechnician()"
                                                @touchcancel="stopDrawingTechnician()"></canvas>
                                            <input type="hidden" name="technician_signature"
                                                x-ref="technicianSignatureInput">
                                        </div>

                                        <div class="flex justify-center mt-4">
                                            <div class="relative">
                                                <button type="button" @click="clearTechnicianSignature()"
                                                    class="geometric-card px-6 py-2 bg-gray-400 text-white font-bold tracking-wide hover:bg-gray-500 transition-colors relative overflow-hidden">
                                                    <div
                                                        class="absolute top-0 right-0 w-4 h-4 bg-gray-600 opacity-40 transform rotate-45 translate-x-2 -translate-y-2">
                                                    </div>
                                                    <span class="relative">BERSIHKAN</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="relative">
                                        <button type="submit" :disabled="!hasTechnicianSignature"
                                            class="w-full geometric-card py-4 bg-primary text-white font-bold text-lg tracking-wide hover:bg-opacity-90 transition-all disabled:bg-gray-300 disabled:cursor-not-allowed relative overflow-hidden">
                                            <div
                                                class="absolute top-0 right-0 w-12 h-12 bg-secondary opacity-30 transform rotate-45 translate-x-6 -translate-y-6">
                                            </div>
                                            <div
                                                class="absolute bottom-0 left-0 w-8 h-8 bg-background opacity-20 transform rotate-45 -translate-x-4 translate-y-4">
                                            </div>
                                            <span class="relative">KIRIM TANDA TANGAN TEKNISI</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <div class="text-center relative">
                                <div
                                    class="geometric-card bg-background bg-opacity-30 p-6 mx-auto max-w-xs relative overflow-hidden">
                                    <div
                                        class="absolute top-0 right-0 w-8 h-8 bg-secondary opacity-40 transform rotate-45 translate-x-4 -translate-y-4">
                                    </div>
                                    <img src="{{ asset('storage/' . $report->technician_signature) }}"
                                        alt="Tanda Tangan Teknisi"
                                        class="max-w-full h-auto max-h-32 mx-auto relative z-10">
                                </div>
                                <p class="mt-4 font-bold text-primary text-lg">{{ $report->technician_name }}</p>
                                <div
                                    class="mt-3 inline-block geometric-card px-6 py-2 bg-secondary text-primary relative overflow-hidden">
                                    <div
                                        class="absolute top-0 right-0 w-4 h-4 bg-primary opacity-30 transform rotate-45 translate-x-2 -translate-y-2">
                                    </div>
                                    <span class="relative font-bold tracking-wide">TELAH DITANDATANGANI</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Geometric Footer -->
            <div class="text-center py-8 relative">
                <div class="geometric-card bg-white px-8 py-4 mx-auto max-w-xs relative overflow-hidden shadow-sm">
                    <div
                        class="absolute top-0 right-0 w-6 h-6 bg-secondary opacity-30 transform rotate-45 translate-x-3 -translate-y-3">
                    </div>
                    <div
                        class="absolute bottom-0 left-0 w-4 h-4 bg-primary opacity-20 transform rotate-45 -translate-x-2 translate-y-2">
                    </div>
                    <p class="relative text-xs text-primary font-medium tracking-wide">
                        © {{ date('Y') }} ECOFORPEST
                    </p>
                    <p class="relative text-xs text-gray-600 mt-1">
                        Professional Pest Control Service
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function serviceReport() {
            return {
                hasSignature: false,
                hasTechnicianSignature: false,
                isDrawing: false,
                isDrawingTechnician: false,
                lastPos: {
                    x: 0,
                    y: 0
                },
                lastPosTechnician: {
                    x: 0,
                    y: 0
                },

                init() {
                    this.setupCanvas();
                    this.setupTechnicianCanvas();
                },

                setupCanvas() {
                    const canvas = this.$refs.signatureCanvas;
                    if (!canvas) return;
                    const ctx = canvas.getContext('2d');
                    ctx.strokeStyle = '#133A1B'; // Primary color
                    ctx.lineWidth = 3;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                },

                setupTechnicianCanvas() {
                    const canvas = this.$refs.technicianSignatureCanvas;
                    if (!canvas) return;
                    const ctx = canvas.getContext('2d');
                    ctx.strokeStyle = '#133A1B'; // Primary color
                    ctx.lineWidth = 3;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                },

                getPosition(e) {
                    const canvas = this.$refs.signatureCanvas;
                    const rect = canvas.getBoundingClientRect();
                    const scaleX = canvas.width / rect.width;
                    const scaleY = canvas.height / rect.height;

                    if (e.touches && e.touches[0]) {
                        return {
                            x: (e.touches[0].clientX - rect.left) * scaleX,
                            y: (e.touches[0].clientY - rect.top) * scaleY
                        };
                    } else {
                        return {
                            x: (e.clientX - rect.left) * scaleX,
                            y: (e.clientY - rect.top) * scaleY
                        };
                    }
                },

                startDrawing(e) {
                    e.preventDefault();
                    this.isDrawing = true;
                    this.lastPos = this.getPosition(e);
                },

                draw(e) {
                    if (!this.isDrawing) return;
                    e.preventDefault();

                    const canvas = this.$refs.signatureCanvas;
                    const ctx = canvas.getContext('2d');
                    const pos = this.getPosition(e);

                    ctx.beginPath();
                    ctx.moveTo(this.lastPos.x, this.lastPos.y);
                    ctx.lineTo(pos.x, pos.y);
                    ctx.stroke();

                    this.lastPos = pos;
                    this.hasSignature = !this.isCanvasBlank();
                },

                stopDrawing() {
                    this.isDrawing = false;
                },

                clearSignature() {
                    const canvas = this.$refs.signatureCanvas;
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    this.hasSignature = false;
                    this.$refs.signatureInput.value = '';
                },

                isCanvasBlank() {
                    const canvas = this.$refs.signatureCanvas;
                    const blank = document.createElement('canvas');
                    blank.width = canvas.width;
                    blank.height = canvas.height;
                    return canvas.toDataURL() === blank.toDataURL();
                },

                submitForm(e) {
                    if (this.isCanvasBlank()) {
                        alert('Silakan tanda tangani terlebih dahulu.');
                        e.preventDefault();
                        return false;
                    }

                    const canvas = this.$refs.signatureCanvas;
                    this.$refs.signatureInput.value = canvas.toDataURL('image/png');
                    return true;
                },

                startDrawingTechnician(e) {
                    e.preventDefault();
                    this.isDrawingTechnician = true;
                    this.lastPosTechnician = this.getTechnicianPosition(e);
                },

                drawTechnician(e) {
                    if (!this.isDrawingTechnician) return;
                    e.preventDefault();

                    const canvas = this.$refs.technicianSignatureCanvas;
                    const ctx = canvas.getContext('2d');
                    const pos = this.getTechnicianPosition(e);

                    ctx.beginPath();
                    ctx.moveTo(this.lastPosTechnician.x, this.lastPosTechnician.y);
                    ctx.lineTo(pos.x, pos.y);
                    ctx.stroke();

                    this.lastPosTechnician = pos;
                    this.hasTechnicianSignature = !this.isTechnicianCanvasBlank();
                },

                stopDrawingTechnician() {
                    this.isDrawingTechnician = false;
                },

                getTechnicianPosition(e) {
                    const canvas = this.$refs.technicianSignatureCanvas;
                    const rect = canvas.getBoundingClientRect();
                    const scaleX = canvas.width / rect.width;
                    const scaleY = canvas.height / rect.height;

                    if (e.touches && e.touches[0]) {
                        return {
                            x: (e.touches[0].clientX - rect.left) * scaleX,
                            y: (e.touches[0].clientY - rect.top) * scaleY
                        };
                    } else {
                        return {
                            x: (e.clientX - rect.left) * scaleX,
                            y: (e.clientY - rect.top) * scaleY
                        };
                    }
                },

                clearTechnicianSignature() {
                    const canvas = this.$refs.technicianSignatureCanvas;
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    this.hasTechnicianSignature = false;
                    this.$refs.technicianSignatureInput.value = '';
                },

                isTechnicianCanvasBlank() {
                    const canvas = this.$refs.technicianSignatureCanvas;
                    const blank = document.createElement('canvas');
                    blank.width = canvas.width;
                    blank.height = canvas.height;
                    return canvas.toDataURL() === blank.toDataURL();
                },

                submitTechnicianForm(e) {
                    if (this.isTechnicianCanvasBlank()) {
                        alert('Silakan tanda tangani terlebih dahulu.');
                        e.preventDefault();
                        return false;
                    }

                    const canvas = this.$refs.technicianSignatureCanvas;
                    this.$refs.technicianSignatureInput.value = canvas.toDataURL('image/png');
                    return true;
                }
            }
        }
    </script>
@endsection
