import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:fintrack_frontend/services/api_service.dart';

class VerifyOtpScreen extends StatefulWidget {
  final String email;
  final bool redirectHome;

  const VerifyOtpScreen({
    super.key,
    required this.email,
    this.redirectHome = true,
  });

  @override
  State<VerifyOtpScreen> createState() => _VerifyOtpScreenState();
}

class _VerifyOtpScreenState extends State<VerifyOtpScreen> {
  final List<TextEditingController> _controllers = List.generate(
    4,
    (_) => TextEditingController(),
  );
  final List<FocusNode> _focusNodes = List.generate(4, (_) => FocusNode());
  bool _loading = false;
  String? _error;

  Future<void> _verify() async {
    final code = _controllers.map((c) => c.text.trim()).join();
    if (code.isEmpty || code.length != 4) {
      return setState(() => _error = 'Enter 4-digit verification code');
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final ok = await ApiService.verifyOtp(widget.email, code);
      if (ok) {
        if (widget.redirectHome) {
          final user = ApiService.currentUser ?? {};
          final dashboard = await ApiService.fetchDashboard();

          // ignore: use_build_context_synchronously
          Navigator.pushReplacementNamed(
            context,
            '/home',
            arguments: {'user': user, 'dashboard': dashboard},
          );
        } else {
          Navigator.pop(context, true);
        }
      }
    } catch (e) {
      setState(() => _error = e.toString());
    }

    setState(() => _loading = false);
  }

  Future<void> _resend() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      await ApiService.resendOtp(widget.email);
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Verification code resent')));
    } catch (e) {
      setState(() => _error = e.toString());
    }

    setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    final bool isLight = Theme.of(context).brightness == Brightness.light;
    final Color bgColor = isLight
        ? const Color(0xFFF9FAFB)
        : const Color(0xFF020617);
    final Color cardBg = isLight ? Colors.white : const Color(0xFF0B1220);
    const Color accentTeal = Color(0xFF14B8A6);
    const Color accentSky = Color(0xFF0EA5E9);
    final Color primaryText = isLight ? const Color(0xFF020617) : Colors.white;
    final Color secondaryText = isLight
        ? const Color(0xFF4B5563)
        : const Color(0xFF94A3B8);
    final Color mutedText = isLight
        ? const Color(0xFF6B7280)
        : const Color(0xFF9CA3AF);
    final Color inputFill = isLight
        ? Colors.white
        : const Color(0xFF020617).withOpacity(0.7);
    final Color inputBorder = isLight
        ? const Color(0xFFE5E7EB)
        : const Color(0xFF1F2937);

    return Scaffold(
      backgroundColor: bgColor,
      body: Container(
        decoration: BoxDecoration(
          gradient: RadialGradient(
            center: Alignment.topLeft,
            radius: 1.2,
            colors: isLight
                ? const [Color(0x33BAE6FD), Color(0x00E5E7EB)]
                : const [Color(0x3314B8A6), Color(0x00020F1F)],
          ),
        ),
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 24),
              constraints: const BoxConstraints(maxWidth: 420),
              decoration: BoxDecoration(
                color: isLight ? cardBg : cardBg.withOpacity(0.96),
                borderRadius: BorderRadius.circular(24),
                border: Border.all(
                  color: isLight
                      ? const Color(0xFFE5E7EB)
                      : Colors.white.withOpacity(0.06),
                ),
                boxShadow: [
                  BoxShadow(
                    blurRadius: 30,
                    offset: const Offset(0, 20),
                    color: isLight
                        ? const Color(0x22000000)
                        : const Color(0x66000000),
                  ),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Container(
                            height: 40,
                            width: 40,
                            decoration: const BoxDecoration(
                              shape: BoxShape.rectangle,
                              borderRadius: BorderRadius.all(
                                Radius.circular(14),
                              ),
                              gradient: LinearGradient(
                                colors: [Color(0xFF14B8A6), Color(0xFF0EA5E9)],
                              ),
                            ),
                            alignment: Alignment.center,
                            child: const Text(
                              'FT',
                              style: TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w700,
                                fontSize: 16,
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'FinTrack',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w600,
                                  color: primaryText,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                'Enter the 4-digit code sent to your email',
                                style: TextStyle(
                                  fontSize: 11,
                                  color: mutedText,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  Text(
                    'We sent a 4-digit verification code to ${widget.email}.',
                    style: TextStyle(fontSize: 12, color: secondaryText),
                  ),
                  const SizedBox(height: 18),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(4, (i) {
                      return Container(
                        width: 56,
                        margin: const EdgeInsets.symmetric(horizontal: 6),
                        child: TextField(
                          autofocus: i == 0,
                          controller: _controllers[i],
                          focusNode: _focusNodes[i],
                          textAlign: TextAlign.center,
                          keyboardType: TextInputType.number,
                          maxLength: 1,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                            LengthLimitingTextInputFormatter(1),
                          ],
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w600,
                            color: primaryText,
                          ),
                          decoration: InputDecoration(
                            counterText: '',
                            filled: true,
                            fillColor: inputFill,
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(14),
                              borderSide: BorderSide(color: inputBorder),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(14),
                              borderSide: BorderSide(color: inputBorder),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(14),
                              borderSide: BorderSide(color: accentTeal),
                            ),
                          ),
                          onChanged: (v) {
                            if (v.length == 1) {
                              if (i + 1 < _focusNodes.length) {
                                _focusNodes[i + 1].requestFocus();
                              }
                            } else if (v.isEmpty) {
                              if (i - 1 >= 0) {
                                _focusNodes[i - 1].requestFocus();
                              }
                            }
                          },
                          onSubmitted: (_) => _verify(),
                        ),
                      );
                    }),
                  ),
                  const SizedBox(height: 12),
                  if (_error != null)
                    Text(
                      _error!,
                      style: const TextStyle(
                        color: Color(0xFFF97373),
                        fontSize: 12,
                      ),
                    ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _loading ? null : _verify,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: accentSky,
                        minimumSize: const Size(double.infinity, 48),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(999),
                        ),
                        elevation: 10,
                        shadowColor: accentSky.withOpacity(0.5),
                      ),
                      child: _loading
                          ? const SizedBox(
                              height: 18,
                              width: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                valueColor: AlwaysStoppedAnimation<Color>(
                                  Colors.white,
                                ),
                              ),
                            )
                          : const Text(
                              'Verify',
                              style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                    ),
                  ),
                  TextButton(
                    onPressed: _loading ? null : _resend,
                    child: const Text(
                      'Resend code',
                      style: TextStyle(fontSize: 12, color: Color(0xFF38BDF8)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    for (final c in _controllers) {
      c.dispose();
    }
    for (final f in _focusNodes) {
      f.dispose();
    }
    super.dispose();
  }
}
