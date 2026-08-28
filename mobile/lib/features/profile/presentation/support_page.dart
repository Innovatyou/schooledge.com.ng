import 'package:flutter/material.dart';
import '../../../core/widgets/module_ui.dart';

class SupportPage extends StatelessWidget {
  const SupportPage({super.key, required this.school});
  final Map<String, dynamic> school;

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('School contact & support')),
    body: ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Text(
          school['school_name']?.toString() ?? 'Your school',
          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900),
        ),
        const SizedBox(height: 16),
        if (school['address'] != null)
          InfoRow(
            icon: Icons.location_on_rounded,
            title: 'Address',
            subtitle: school['address'].toString(),
            color: const Color(0xff168aad),
            trailing: const SizedBox.shrink(),
          ),
        if (school['email'] != null)
          InfoRow(
            icon: Icons.email_rounded,
            title: 'Email',
            subtitle: school['email'].toString(),
            color: const Color(0xff725cff),
            trailing: const SizedBox.shrink(),
          ),
        if (school['mobileno'] != null)
          InfoRow(
            icon: Icons.phone_rounded,
            title: 'Phone',
            subtitle: school['mobileno'].toString(),
            color: const Color(0xff00a896),
            trailing: const SizedBox.shrink(),
          ),
      ],
    ),
  );
}
