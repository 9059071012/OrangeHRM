/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

import JobTitle from '@/orangehrmAdminPlugin/pages/jobTitle/JobTitle.vue';
import EditJobTitle from '@/orangehrmAdminPlugin/pages/jobTitle/EditJobTitle.vue';
import SaveJobTitle from '@/orangehrmAdminPlugin/pages/jobTitle/SaveJobTitle.vue';
import JobCategory from '@/orangehrmAdminPlugin/pages/jobCategory/JobCategory.vue';
import EditJobCategory from '@/orangehrmAdminPlugin/pages/jobCategory/EditJobCategory.vue';
import SaveJobCategory from '@/orangehrmAdminPlugin/pages/jobCategory/SaveJobCategory.vue';
import authenticationPages from './orangehrmAuthenticationPlugin/pages';
import QualificationSkill from '@/orangehrmAdminPlugin/pages/qualificationSkill/QualificationSkill.vue';
import EditQualificationSkill from '@/orangehrmAdminPlugin/pages/qualificationSkill/EditQualificationSkill.vue';
import SaveQualificationSkill from '@/orangehrmAdminPlugin/pages/qualificationSkill/SaveQualificationSkill.vue';

export default {
  'job-title-list': JobTitle,
  'job-title-edit': EditJobTitle,
  'job-title-save': SaveJobTitle,
  'job-category-list': JobCategory,
  'job-category-edit': EditJobCategory,
  'job-category-save': SaveJobCategory,
  ...authenticationPages,
  'qualification-skill-list': QualificationSkill,
  'qualification-skill-edit': EditQualificationSkill,
  'qualification-skill-save': SaveQualificationSkill,
};
