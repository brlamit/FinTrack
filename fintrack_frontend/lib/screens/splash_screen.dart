import 'package:flutter/material.dart';
import 'package:fintrack_frontend/screens/login/login_screen.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  Widget build(BuildContext context) {
    final height = MediaQuery.of(context).size.height;
    final bool isLight = Theme.of(context).brightness == Brightness.light;
    final Color bgColor = isLight
        ? const Color(0xFFF9FAFB)
        : const Color(0xFF020617);
    final Color cardBg = isLight ? Colors.white : const Color(0xFF0B1220);
    const Color accentSky = Color(0xFF0EA5E9);
    final Color primaryText = isLight ? const Color(0xFF020617) : Colors.white;
    final Color secondaryText = isLight
        ? const Color(0xFF4B5563)
        : const Color(0xFF94A3B8);
    final Color mutedText = isLight
        ? const Color(0xFF6B7280)
        : const Color(0xFF9CA3AF);

    return Scaffold(
      backgroundColor: bgColor,
      body: Stack(
        children: [
          // background gradient + soft blobs
          Container(
            decoration: BoxDecoration(
              gradient: RadialGradient(
                center: Alignment.topLeft,
                radius: 1.3,
                colors: isLight
                    ? const [Color(0x33BAE6FD), Color(0x00E5E7EB)]
                    : const [Color(0x4414B8A6), Color(0x00020F1F)],
              ),
            ),
          ),
          Positioned(
            top: -80,
            right: -40,
            child: Container(
              height: 180,
              width: 180,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: LinearGradient(
                  colors: [const Color(0x3314B8A6), const Color(0x330EA5E9)],
                ),
              ),
            ),
          ),
          Positioned(
            bottom: -100,
            left: -40,
            child: Container(
              height: 200,
              width: 200,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: LinearGradient(
                  colors: [const Color(0x3338BDF8), const Color(0x3314B8A6)],
                ),
              ),
            ),
          ),
          Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 22,
                  vertical: 24,
                ),
                constraints: const BoxConstraints(maxWidth: 460),
                decoration: BoxDecoration(
                  color: isLight ? cardBg : cardBg.withOpacity(0.96),
                  borderRadius: BorderRadius.circular(28),
                  border: Border.all(
                    color: isLight
                        ? const Color(0xFFE5E7EB)
                        : Colors.white.withOpacity(0.06),
                  ),
                  boxShadow: [
                    BoxShadow(
                      blurRadius: 40,
                      offset: const Offset(0, 24),
                      color: isLight
                          ? const Color(0x22000000)
                          : const Color(0x66000000),
                    ),
                  ],
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            Container(
                              height: 44,
                              width: 44,
                              decoration: const BoxDecoration(
                                shape: BoxShape.rectangle,
                                borderRadius: BorderRadius.all(
                                  Radius.circular(16),
                                ),
                                gradient: LinearGradient(
                                  colors: [
                                    Color(0xFF14B8A6),
                                    Color(0xFF0EA5E9),
                                  ],
                                ),
                              ),
                              alignment: Alignment.center,
                              child: const Text(
                                'FT',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w700,
                                  fontSize: 18,
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'FinTrack',
                                  style: TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.w600,
                                    color: primaryText,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  'Smart money tracking made simple',
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
                    SizedBox(height: height * 0.04),
                    Text(
                      'Track, save and grow your money.',
                      style: TextStyle(
                        color: primaryText,
                        fontSize: 22,
                        fontWeight: FontWeight.w600,
                        height: 1.3,
                      ),
                    ),
                    const SizedBox(height: 18),
                    // mini analytics preview card
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(20),
                        gradient: const LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: [Color(0x1A22C55E), Color(0x1A0EA5E9)],
                        ),
                        border: Border.all(
                          color: Colors.white.withOpacity(0.08),
                        ),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'This month balance',
                                  style: TextStyle(
                                    color: mutedText,
                                    fontSize: 11,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Rs 42,380',
                                  style: TextStyle(
                                    color: primaryText,
                                    fontSize: 18,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                const Text(
                                  '+ 18.4% vs last month',
                                  style: TextStyle(
                                    color: Color(0xFF4ADE80),
                                    fontSize: 11,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 12),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              _buildPill('Daily', Colors.white),
                              const SizedBox(height: 6),
                              _buildPill('Weekly', const Color(0xFF38BDF8)),
                              const SizedBox(height: 6),
                              _buildPill('Monthly', const Color(0xFF22C55E)),
                            ],
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: height * 0.045),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () {
                          Navigator.pushReplacement(
                            context,
                            MaterialPageRoute(
                              builder: (_) => const LoginScreen(),
                            ),
                          );
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: accentSky,
                          minimumSize: const Size(double.infinity, 50),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(999),
                          ),
                          elevation: 10,
                          shadowColor: accentSky.withOpacity(0.5),
                        ),
                        child: const Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              'Let\'s start',
                              style: TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.w600,
                                color: Colors.white,
                              ),
                            ),
                            SizedBox(width: 8),
                            Icon(
                              Icons.arrow_right_alt,
                              color: Colors.white,
                              size: 22,
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

Widget _buildPill(String label, Color dotColor) {
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
    decoration: BoxDecoration(
      color: const Color(0x33020F1F),
      borderRadius: BorderRadius.circular(999),
      border: Border.all(color: Colors.white.withOpacity(0.08)),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          height: 6,
          width: 6,
          decoration: BoxDecoration(color: dotColor, shape: BoxShape.circle),
        ),
        const SizedBox(width: 6),
        Text(
          label,
          style: const TextStyle(color: Color(0xFFE5E7EB), fontSize: 11),
        ),
      ],
    ),
  );
}
