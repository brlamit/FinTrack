import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

class VerifyEmailScreen extends StatelessWidget {
  final String email;

  const VerifyEmailScreen({super.key, required this.email});

  Future<void> resendEmail() async {
    await Supabase.instance.client.auth.resend(
      type: OtpType.signup,
      email: email,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Verify Email")),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Text("Check your email for verification link.",
                  style: TextStyle(fontSize: 18)),

              const SizedBox(height: 20),

              Text("Email: $email",
                  style: const TextStyle(fontWeight: FontWeight.bold)),

              const SizedBox(height: 30),

              ElevatedButton(
                onPressed: resendEmail,
                child: const Text("Resend Verification Email"),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
