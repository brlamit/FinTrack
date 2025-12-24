import 'package:fintrack_frontend/screens/login/forgot_password_screen.dart';
import 'package:fintrack_frontend/screens/login/login_screen.dart';
import 'package:fintrack_frontend/screens/login/signup_screen.dart';
import 'package:fintrack_frontend/screens/splash_screen.dart';
import 'package:fintrack_frontend/screens/home/views/home_screen.dart';
import 'package:flutter/material.dart';

class MyAppView extends StatefulWidget {
  const MyAppView({super.key});

  static void toggleTheme(BuildContext context) {
    final state = context.findAncestorStateOfType<_MyAppViewState>();
    state?._toggleTheme();
  }

  @override
  State<MyAppView> createState() => _MyAppViewState();
}

class _MyAppViewState extends State<MyAppView> {
  ThemeMode _themeMode = ThemeMode.light;

  void _toggleTheme() {
    setState(() {
      _themeMode = _themeMode == ThemeMode.light
          ? ThemeMode.dark
          : ThemeMode.light;
    });
  }

  @override
  Widget build(BuildContext context) {
    final lightTheme = ThemeData(
      colorScheme: ColorScheme.light(
        surface: const Color(0xFFF8FAFC), // Matches web light background
        onSurface: const Color(0xFF020617), // Matches web light text color
        primary: const Color(0xFF0D6EFD), // Bootstrap primary blue
        secondary: const Color(0xFF198754), // Bootstrap success green
        tertiary: const Color(0xFFFFC107), // Bootstrap warning yellow
        error: const Color(0xFFDC3545), // Bootstrap danger red
        outline: Colors.grey[500],
      ),
      cardColor:
          Colors.white, // Explicitly set card color to white for light mode
    );

    final darkTheme = ThemeData(
      brightness: Brightness.dark,
      colorScheme: const ColorScheme.dark(
        surface: Color(0xFF020617), // Matches web dark background
        onSurface: Color(0xFFE5E7EB), // Matches web dark text color
        primary: Color(0xFF0D6EFD), // Bootstrap primary blue
        secondary: Color(0xFF198754), // Bootstrap success green
        tertiary: Color(0xFFFFC107), // Bootstrap warning yellow
        error: Color(0xFFDC3545), // Bootstrap danger red
        outline: Color(0xFF9CA3AF), // Matches web muted text
      ),
      cardColor: const Color(0xFF020617), // Explicitly set for dark mode
    );

    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: "Expense Teacker",
      themeMode: _themeMode,
      theme: lightTheme,
      darkTheme: darkTheme,
      initialRoute: '/splash',
      routes: {
        '/splash': (_) => const SplashScreen(),
        '/login': (_) => const LoginScreen(),
        '/register': (_) => const SignupScreen(),
        '/forgot-password': (_) => const ForgotPasswordScreen(),
        '/home': (context) {
          final args =
              ModalRoute.of(context)!.settings.arguments
                  as Map<String, dynamic>;

          final user = args['user'] as Map<String, dynamic>;
          final dashboard = args['dashboard'] as Map<String, dynamic>;

          return HomeScreen(user: user, dashboard: dashboard);
        },
      },
      onUnknownRoute: (settings) {
        return MaterialPageRoute(builder: (_) => const LoginScreen());
      },
      builder: (context, child) => child ?? const SizedBox.shrink(),
    );
  }
}
