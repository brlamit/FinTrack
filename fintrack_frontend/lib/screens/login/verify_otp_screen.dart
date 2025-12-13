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
    return Scaffold(
      appBar: AppBar(title: const Text('Verify account')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('We sent a 4-digit verification code to ${widget.email}.'),
            const SizedBox(height: 12),
            // Four separate digit fields to match web UI
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
                      // Only digits and limit to single char
                      FilteringTextInputFormatter.digitsOnly,
                      LengthLimitingTextInputFormatter(1),
                    ],
                    decoration: const InputDecoration(counterText: ''),
                    onChanged: (v) {
                      if (v.length == 1) {
                        // If user pasted full code into first field
                        if (i == 0 && v.length > 1) {
                          final pasted = v;
                          for (var k = 0; k < 4 && k < pasted.length; k++) {
                            _controllers[k].text = pasted[k];
                          }
                          // move focus to last
                          _focusNodes[3].requestFocus();
                        } else {
                          if (i + 1 < _focusNodes.length) {
                            _focusNodes[i + 1].requestFocus();
                          }
                        }
                      } else if (v.isEmpty) {
                        if (i - 1 >= 0) _focusNodes[i - 1].requestFocus();
                      }
                    },
                    onSubmitted: (_) => _verify(),
                  ),
                );
              }),
            ),
            const SizedBox(height: 12),
            if (_error != null)
              Text(_error!, style: const TextStyle(color: Colors.red)),
            const SizedBox(height: 12),
            ElevatedButton(
              onPressed: _loading ? null : _verify,
              child: _loading
                  ? const CircularProgressIndicator()
                  : const Text('Verify'),
            ),
            TextButton(
              onPressed: _loading ? null : _resend,
              child: const Text('Resend code'),
            ),
          ],
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
