import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final emailController = TextEditingController();

  Future<void> sendReset() async {
    try {
      await Supabase.instance.client.auth.resetPasswordForEmail(
        emailController.text.trim(),
      );

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Password reset email sent.")),
      );
    } catch (e) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text("Error: $e")));
    }
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
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
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
                                'Reset your password',
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
                    'Enter your email to reset your password.',
                    style: TextStyle(fontSize: 12, color: secondaryText),
                  ),
                  const SizedBox(height: 20),
                  TextField(
                    controller: emailController,
                    style: TextStyle(color: primaryText, fontSize: 13),
                    decoration: InputDecoration(
                      labelText: 'Email',
                      labelStyle: TextStyle(
                        color: isLight
                            ? const Color(0xFF6B7280)
                            : const Color(0xFFCBD5F5),
                        fontSize: 12,
                      ),
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
                  ),
                  const SizedBox(height: 22),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: sendReset,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: accentSky,
                        minimumSize: const Size(double.infinity, 48),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(999),
                        ),
                        elevation: 10,
                        shadowColor: accentSky.withOpacity(0.5),
                      ),
                      child: const Text(
                        'Send reset link',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
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
}
