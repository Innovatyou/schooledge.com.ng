import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/depth_card.dart';
import '../../../core/widgets/module_ui.dart';
import '../../classmate_chat/presentation/chat_oversight_page.dart';
import '../data/admin_repository.dart';
import 'approvals_page.dart';
import 'broadcast_page.dart';
import 'lookup_page.dart';
import '../../../core/navigation/page_transitions.dart';

class AdminDashboardPage extends ConsumerWidget {
  const AdminDashboardPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) => ModulePage(
    title: 'Admin dashboard',
    subtitle: 'A quick overview and the essentials on the go.',
    icon: Icons.admin_panel_settings_rounded,
    colors: const [Color(0xff163a70), Color(0xff2a5298)],
    children: [
      ref
          .watch(adminSummaryProvider)
          .when(
            loading: () => const Padding(
              padding: EdgeInsets.symmetric(vertical: 60),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (error, _) => InfoRow(
              icon: Icons.refresh_rounded,
              title: 'Could not load the dashboard',
              subtitle: 'Tap to try again',
              color: const Color(0xffff6b6b),
              onTap: () => ref.invalidate(adminSummaryProvider),
            ),
            data: (summary) {
              final attendance = summary['attendance_today'] as Map;
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      StatTile(
                        value: '${summary['students']}',
                        label: 'Students',
                        icon: Icons.school_rounded,
                        color: const Color(0xff725cff),
                      ),
                      const SizedBox(width: 12),
                      StatTile(
                        value: '${summary['staff']}',
                        label: 'Staff',
                        icon: Icons.badge_rounded,
                        color: const Color(0xff00a896),
                      ),
                      const SizedBox(width: 12),
                      StatTile(
                        value: '${summary['parents']}',
                        label: 'Parents',
                        icon: Icons.family_restroom_rounded,
                        color: const Color(0xffffa62b),
                      ),
                    ],
                  ),
                  const SectionTitle('Attendance today'),
                  DepthCard(
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          attendance['percent'] != null
                              ? '${attendance['present']} of ${attendance['marked']} present (${attendance['percent']}%)'
                              : 'Not yet taken today',
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ],
                    ),
                  ),
                  const SectionTitle('Management'),
                  InfoRow(
                    icon: Icons.fact_check_rounded,
                    title: 'Approvals',
                    subtitle: '${summary['pending_approvals']} pending',
                    color: const Color(0xff168aad),
                    onTap: () => Navigator.of(
                      context,
                    ).push(moduleRoute<void>(const ApprovalsPage())),
                  ),
                  InfoRow(
                    icon: Icons.campaign_rounded,
                    title: 'Broadcast announcement',
                    subtitle: 'Send a school-wide message',
                    color: const Color(0xffd64545),
                    onTap: () => Navigator.of(
                      context,
                    ).push(moduleRoute<void>(const BroadcastPage())),
                  ),
                  InfoRow(
                    icon: Icons.search_rounded,
                    title: 'Lookup',
                    subtitle: 'Find a student, staff member or parent',
                    color: const Color(0xff725cff),
                    onTap: () => Navigator.of(
                      context,
                    ).push(moduleRoute<void>(const LookupPage())),
                  ),
                  InfoRow(
                    icon: Icons.shield_rounded,
                    title: 'Chat oversight',
                    subtitle: 'Review classmate chat activity by class',
                    color: const Color(0xff00a896),
                    onTap: () => Navigator.of(
                      context,
                    ).push(moduleRoute<void>(const ChatOversightPage())),
                  ),
                ],
              );
            },
          ),
    ],
  );
}
